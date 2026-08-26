<?php
/**
 * CampusFlow - Complete All-in-One Database Tables Viewer
 * Displays all 9 MySQL tables and their records on a single visual PHP page
 */

declare(strict_types=1);

require_once __DIR__ . '/backend/db.php';

$tables_list = [
    'users' => [
        'title' => 'Users & Credentials',
        'icon' => '👥',
        'desc' => 'Stores login authentication credentials, hashed passwords, contact emails, and role assignments.'
    ],
    'students' => [
        'title' => 'Student Profiles',
        'icon' => '🎓',
        'desc' => 'Stores student register numbers, department, year, section, hostel status, and linked parent ID.'
    ],
    'parents' => [
        'title' => 'Parent Profiles',
        'icon' => '👨‍👩‍👧',
        'desc' => 'Stores parent names, phone numbers for SMS/Voice login, linked child reg numbers, and preferred language for TTS.'
    ],
    'faculty' => [
        'title' => 'Faculty & Staff Directory',
        'icon' => '🏛️',
        'desc' => 'Stores Class Advisors, Department HODs, and Hostel Wardens with their assigned sections and blocks.'
    ],
    'leave_requests' => [
        'title' => 'Leave Requests & Outpasses',
        'icon' => '📄',
        'desc' => 'Core table tracking leave applications, categories, from/to dates, reasons, destination addresses, and workflow stage.'
    ],
    'approvals' => [
        'title' => 'Multi-Tier Approvals Audit Trail',
        'icon' => '✅',
        'desc' => 'Immutable audit log recording approvals and rejections by Parent, Advisor, HOD, and Warden.'
    ],
    'voice_samples' => [
        'title' => 'Parent Voice Biometric Profiles',
        'icon' => '🎙️',
        'desc' => 'Stores baseline parent voice registration samples and biometric acoustic features.'
    ],
    'voice_verifications' => [
        'title' => 'Voice Verification Audit Logs',
        'icon' => '🛡️',
        'desc' => 'Logs live spoken approval transcripts, match confidence scores (threshold >= 70%), and verification status.'
    ],
    'notifications' => [
        'title' => 'System Notifications & Alerts',
        'icon' => '🔔',
        'desc' => 'In-app notification queue for pending student applications, parent approvals, and warden outpasses.'
    ]
];

$database_data = [];
$total_records = 0;

foreach ($tables_list as $tbl_name => $meta) {
    try {
        $stmt = $pdo->query("SELECT * FROM `{$tbl_name}` ORDER BY id DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $database_data[$tbl_name] = $rows;
        $total_records += count($rows);
    } catch (Exception $e) {
        $database_data[$tbl_name] = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Database Tables – CampusFlow</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .tables-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #ffffff;
            padding: 2.5rem 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }
        .quick-nav-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        .quick-nav-pill {
            background: rgba(255, 255, 255, 0.12);
            color: #f8fafc;
            text-decoration: none;
            padding: 0.45rem 0.9rem;
            border-radius: var(--radius-full);
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all var(--transition-fast);
        }
        .quick-nav-pill:hover {
            background: #ffffff;
            color: #0f172a;
        }
        .table-section-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2.5rem;
            overflow: hidden;
        }
        .table-section-header {
            padding: 1.25rem 1.5rem;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .table-section-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .table-section-title h2 {
            font-size: 1.25rem;
            color: #0f172a;
            margin: 0;
        }
        .table-desc {
            padding: 0.75rem 1.5rem;
            background: #ffffff;
            font-size: 0.85rem;
            color: #64748b;
            border-bottom: 1px dashed #e2e8f0;
        }
        .code-snippet {
            font-family: monospace;
            background: #f1f5f9;
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            color: #0f172a;
            font-size: 0.85rem;
        }
    </style>
</head>
<body style="background: #f8fafc; color: var(--text-main); min-height: 100vh;">

    <!-- Top Navigation Header -->
    <header class="main-header">
        <div class="container nav-wrapper">
            <a href="index.html" class="brand-logo">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                </svg>
                CampusFlow
                <span class="brand-badge">Database Tables Viewer</span>
            </a>

            <div class="nav-actions">
                <a href="login.html" class="btn btn-primary btn-sm">Portal Login →</a>
                <a href="setup.php" class="btn btn-secondary btn-sm">Reset / Re-Seed</a>
                <a href="db_viewer.php" class="btn btn-secondary btn-sm">SQL Console</a>
            </div>
        </div>
    </header>

    <main class="container" style="padding: 2rem 1.5rem;">
        
        <!-- Overview Banner -->
        <div class="tables-hero">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 1.85rem; margin-bottom: 0.4rem;">📊 CampusFlow Database Architecture</h1>
                    <p style="color: #cbd5e1; font-size: 0.95rem;">MySQL Database: <code style="color:#38bdf8;">campusflow</code> | 9 Normalized Tables | Total Records: <strong><?= $total_records ?></strong></p>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()" style="background: rgba(255,255,255,0.15); color: #fff; border-color: rgba(255,255,255,0.3);">
                        🖨️ Print Schema Report
                    </button>
                </div>
            </div>

            <!-- Quick Jump Anchor Bar -->
            <div class="quick-nav-bar">
                <?php foreach ($tables_list as $tbl => $meta): ?>
                    <a href="#table-<?= $tbl ?>" class="quick-nav-pill">
                        <?= $meta['icon'] ?> <?= $tbl ?> (<?= count($database_data[$tbl]) ?>)
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Render All 9 Tables -->
        <?php foreach ($tables_list as $tbl => $meta): ?>
            <?php 
                $rows = $database_data[$tbl];
                $rowCount = count($rows);
            ?>
            <section class="table-section-card" id="table-<?= $tbl ?>">
                <div class="table-section-header">
                    <div class="table-section-title">
                        <span style="font-size: 1.6rem;"><?= $meta['icon'] ?></span>
                        <div>
                            <h2>Table: <code><?= htmlspecialchars($tbl) ?></code></h2>
                            <div style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($meta['title']) ?></div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span class="status-badge status-blue" style="font-size: 0.85rem; font-weight: 700;">
                            <?= $rowCount ?> <?= $rowCount === 1 ? 'record' : 'records' ?>
                        </span>
                        <a href="db_viewer.php?table=<?= urlencode($tbl) ?>" class="btn btn-secondary btn-sm" style="font-size: 0.8rem;">
                            Inspect in SQL Console ↗
                        </a>
                    </div>
                </div>

                <div class="table-desc">
                    <strong>Description:</strong> <?= htmlspecialchars($meta['desc']) ?>
                </div>

                <?php if (empty($rows)): ?>
                    <div style="padding: 2.5rem; text-align: center; color: #94a3b8;">
                        <p>No records found in table <code><?= htmlspecialchars($tbl) ?></code>.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="custom-table">
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
                                                    <span class="status-badge <?= 
                                                        (strpos(strtolower((string)$val), 'approved') !== false || strpos(strtolower((string)$val), 'completed') !== false) ? 'status-green' : 
                                                        ((strpos(strtolower((string)$val), 'rejected') !== false) ? 'status-red' : 'status-yellow') 
                                                    ?>">
                                                        <?= htmlspecialchars((string)$val) ?>
                                                    </span>
                                                <?php elseif ($key === 'role' || $key === 'hostel_status' || $key === 'leave_type'): ?>
                                                    <span class="meta-pill" style="font-weight: 600; text-transform: capitalize;">
                                                        <?= htmlspecialchars(str_replace('_', ' ', (string)$val)) ?>
                                                    </span>
                                                <?php elseif ($key === 'password_hash'): ?>
                                                    <span style="color:#94a3b8; font-family:monospace; font-size:0.75rem;" title="<?= htmlspecialchars((string)$val) ?>">
                                                        <?= substr((string)$val, 0, 18) ?>...
                                                    </span>
                                                <?php elseif ($key === 'audio_sample_path' || $key === 'audio_path'): ?>
                                                    <span class="code-snippet" title="<?= htmlspecialchars((string)$val) ?>">
                                                        <?= htmlspecialchars(basename((string)$val)) ?>
                                                    </span>
                                                <?php elseif ($key === 'is_verified'): ?>
                                                    <span class="status-badge <?= $val ? 'status-green' : 'status-red' ?>">
                                                        <?= $val ? '✓ Verified' : '✗ Failed' ?>
                                                    </span>
                                                <?php elseif ($key === 'match_score'): ?>
                                                    <strong style="color: <?= (float)$val >= 70 ? '#059669' : '#dc2626' ?>;">
                                                        <?= htmlspecialchars((string)$val) ?>%
                                                    </strong>
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
            </section>
        <?php endforeach; ?>

    </main>

</body>
</html>
