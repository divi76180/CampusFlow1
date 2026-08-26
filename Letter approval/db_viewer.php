<?php
/**
 * CampusFlow - Interactive Database Explorer & Management Interface
 */

declare(strict_types=1);

require_once __DIR__ . '/backend/db.php';

$all_tables = [
    'users',
    'students',
    'parents',
    'faculty',
    'leave_requests',
    'approvals',
    'voice_samples',
    'voice_verifications',
    'notifications'
];

$selected_table = $_GET['table'] ?? 'users';
if (!in_array($selected_table, $all_tables, true)) {
    $selected_table = 'users';
}

$sql_query    = trim($_POST['sql_query'] ?? '');
$query_result = null;
$query_error  = null;
$action_msg   = null;

// Handle Custom SQL Execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($sql_query)) {
    try {
        $stmt = $pdo->prepare($sql_query);
        $stmt->execute();
        if (stripos($sql_query, 'SELECT') === 0) {
            $query_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $affected = $stmt->rowCount();
            $action_msg = "Query executed successfully. Affected rows: {$affected}";
        }
    } catch (Exception $e) {
        $query_error = $e->getMessage();
    }
}

// Fetch rows for selected table safely
$rows = [];
try {
    $data_stmt = $pdo->query("SELECT * FROM `{$selected_table}` ORDER BY id DESC LIMIT 100");
    $rows = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $query_error = $e->getMessage();
}

// Get counts for all tables safely
$table_counts = [];
foreach ($all_tables as $tbl) {
    try {
        $cstmt = $pdo->query("SELECT COUNT(*) FROM `{$tbl}`");
        $table_counts[$tbl] = (int)$cstmt->fetchColumn();
    } catch (Exception $e) {
        $table_counts[$tbl] = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Interface – CampusFlow</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .db-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: calc(100vh - 72px);
            background: #0f172a;
            color: #f8fafc;
        }
        .db-sidebar {
            background: #1e293b;
            border-right: 1px solid #334155;
            padding: 1.5rem 1rem;
        }
        .db-sidebar h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 1rem;
            padding-left: 0.5rem;
        }
        .table-nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }
        .table-nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.65rem 0.85rem;
            border-radius: var(--radius-md);
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all var(--transition-fast);
        }
        .table-nav-link:hover {
            background: #334155;
            color: #ffffff;
        }
        .table-nav-link.active {
            background: var(--primary);
            color: #ffffff;
            font-weight: 700;
        }
        .count-tag {
            background: rgba(0, 0, 0, 0.25);
            padding: 0.15rem 0.5rem;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 700;
        }
        .db-main {
            padding: 2rem;
            overflow-y: auto;
        }
        .query-console {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            margin-bottom: 2rem;
        }
        .sql-textarea {
            width: 100%;
            background: #0f172a;
            color: #38bdf8;
            font-family: 'Consolas', 'Fira Code', monospace;
            font-size: 0.95rem;
            padding: 0.85rem;
            border: 1px solid #334155;
            border-radius: var(--radius-md);
            resize: vertical;
            min-height: 80px;
        }
        .db-table-wrapper {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .db-custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .db-custom-table th {
            background: #0f172a;
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid #334155;
            text-align: left;
        }
        .db-custom-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #293548;
            color: #f1f5f9;
        }
        .db-custom-table tr:hover {
            background: #273549;
        }
        .preset-btn {
            background: #334155;
            color: #e2e8f0;
            border: 1px solid #475569;
            padding: 0.3rem 0.65rem;
            border-radius: 4px;
            font-size: 0.78rem;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .preset-btn:hover {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="main-header" style="background: #1e293b; border-color: #334155;">
        <div class="container nav-wrapper" style="max-width: 100%; padding: 0 2rem;">
            <a href="index.html" class="brand-logo" style="color: #ffffff;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2.2">
                    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                </svg>
                CampusFlow Database Explorer
                <span class="brand-badge" style="background: #0369a1; color: #ffffff;">MySQL 8.x</span>
            </a>

            <div class="nav-actions">
                <a href="setup.php" class="btn btn-secondary btn-sm" style="background:#0f172a; color:#fff; border-color:#475569;">
                    🔄 Reset / Seed DB
                </a>
                <a href="login.html" class="btn btn-primary btn-sm">
                    Portal Login →
                </a>
            </div>
        </div>
    </header>

    <div class="db-layout">
        
        <!-- Left Sidebar: Tables List -->
        <aside class="db-sidebar">
            <h3>Database Tables (<?= count($all_tables) ?>)</h3>
            <ul class="table-nav-list">
                <?php foreach ($all_tables as $tbl): ?>
                    <li>
                        <a href="db_viewer.php?table=<?= urlencode($tbl) ?>" class="table-nav-link <?= $selected_table === $tbl ? 'active' : '' ?>">
                            <span>📁 <?= htmlspecialchars($tbl) ?></span>
                            <span class="count-tag"><?= $table_counts[$tbl] ?? 0 ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div style="margin-top: 2rem; padding: 1rem; background: #0f172a; border-radius: var(--radius-md); border: 1px solid #334155;">
                <div style="font-size: 0.8rem; font-weight: 700; color: #38bdf8; margin-bottom: 0.25rem;">Server Connection</div>
                <div style="font-size: 0.75rem; color: #94a3b8;">Host: 127.0.0.1:3306</div>
                <div style="font-size: 0.75rem; color: #94a3b8;">Database: campusflow</div>
                <div style="font-size: 0.75rem; color: #059669; font-weight: 600; margin-top: 0.4rem;">● Connected (PDO)</div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="db-main">

            <!-- SQL Query Console -->
            <div class="query-console">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                    <h2 style="font-size:1.1rem; color:#f8fafc; font-weight:700;">SQL Query Console</h2>
                    <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                        <button type="button" class="preset-btn" onclick="setQuery('SELECT * FROM leave_requests ORDER BY id DESC')">Leaves</button>
                        <button type="button" class="preset-btn" onclick="setQuery('SELECT u.id, u.username, u.role, s.full_name, s.department FROM users u LEFT JOIN students s ON s.user_id = u.id')">Students</button>
                        <button type="button" class="preset-btn" onclick="setQuery('SELECT * FROM voice_verifications ORDER BY id DESC')">Voice Audits</button>
                        <button type="button" class="preset-btn" onclick="setQuery('SELECT * FROM approvals ORDER BY id DESC')">Approvals</button>
                    </div>
                </div>

                <form method="POST" action="db_viewer.php?table=<?= urlencode($selected_table) ?>">
                    <textarea name="sql_query" id="sqlQueryInput" class="sql-textarea" placeholder="Enter SQL query (e.g. SELECT * FROM leave_requests WHERE status = 'Waiting for Parent')"><?= htmlspecialchars($sql_query ?: "SELECT * FROM `{$selected_table}` ORDER BY id DESC LIMIT 50") ?></textarea>
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:0.75rem;">
                        <span style="font-size:0.8rem; color:#94a3b8;">Execute direct query on <code>campusflow</code> database</span>
                        <button type="submit" class="btn btn-primary btn-sm">▶ Execute Query</button>
                    </div>
                </form>

                <?php if ($action_msg): ?>
                    <div class="alert alert-success" style="margin-top: 1rem;"><?= htmlspecialchars($action_msg) ?></div>
                <?php endif; ?>

                <?php if ($query_error): ?>
                    <div class="alert alert-danger" style="margin-top: 1rem;"><strong>SQL Error:</strong> <?= htmlspecialchars($query_error) ?></div>
                <?php endif; ?>
            </div>

            <!-- Custom Query Results (if executed) -->
            <?php if ($query_result !== null): ?>
                <div class="section-toolbar">
                    <h2>Query Results (<?= count($query_result) ?> rows)</h2>
                </div>
                <div class="db-table-wrapper">
                    <?php if (empty($query_result)): ?>
                        <div style="padding: 2rem; text-align: center; color: #94a3b8;">Query returned 0 rows.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="db-custom-table">
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($query_result[0]) as $col): ?>
                                            <th><?= htmlspecialchars($col) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($query_result as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $val): ?>
                                                <td><?= htmlspecialchars((string)($val ?? 'NULL')) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Table Header & Summary -->
            <div class="section-toolbar">
                <div>
                    <h2>Table: <code><?= htmlspecialchars($selected_table) ?></code></h2>
                    <p style="color: #94a3b8; font-size: 0.85rem;">Showing latest <?= count($rows) ?> records (Total: <?= $table_counts[$selected_table] ?? 0 ?> rows)</p>
                </div>
            </div>

            <!-- Table Rows Data Grid -->
            <div class="db-table-wrapper">
                <?php if (empty($rows)): ?>
                    <div style="padding: 3rem; text-align: center; color: #94a3b8;">
                        <h4>No records in `<?= htmlspecialchars($selected_table) ?>` table</h4>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="db-custom-table">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($rows[0]) as $col): ?>
                                        <th><?= htmlspecialchars($col) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <?php foreach ($r as $key => $val): ?>
                                            <td>
                                                <?php if ($key === 'status'): ?>
                                                    <span class="status-badge <?= (strpos(strtolower((string)$val), 'approved') !== false || strpos(strtolower((string)$val), 'completed') !== false) ? 'status-green' : ((strpos(strtolower((string)$val), 'rejected') !== false) ? 'status-red' : 'status-yellow') ?>">
                                                        <?= htmlspecialchars((string)$val) ?>
                                                    </span>
                                                <?php elseif ($key === 'password_hash'): ?>
                                                    <span style="color:#64748b; font-family:monospace; font-size:0.75rem;" title="<?= htmlspecialchars((string)$val) ?>"><?= substr((string)$val, 0, 18) ?>...</span>
                                                <?php elseif ($key === 'role' || $key === 'hostel_status'): ?>
                                                    <span class="meta-pill" style="font-size:0.75rem;"><?= htmlspecialchars((string)$val) ?></span>
                                                <?php else: ?>
                                                    <?= htmlspecialchars((string)($val ?? 'NULL')) ?>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <script>
        function setQuery(sql) {
            document.getElementById('sqlQueryInput').value = sql;
        }
    </script>
</body>
</html>
