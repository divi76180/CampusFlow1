<?php
/**
 * CampusFlow - One-Click Database Initialization & Migration Script
 */

declare(strict_types=1);

$db_host = '127.0.0.1';
$db_port = '3306';
$db_user = 'root';
$db_pass = '';
$sql_file = __DIR__ . '/database/campusflow.sql';

$output_logs = [];
$success = true;

try {
    // 1. Connect to MySQL server without selecting DB first
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $output_logs[] = "Connected to MySQL server at {$db_host}:{$db_port} successfully.";

    // 2. Read SQL file
    if (!file_exists($sql_file)) {
        throw new Exception("SQL schema file not found at: {$sql_file}");
    }

    $sql_content = file_get_contents($sql_file);
    if (empty($sql_content)) {
        throw new Exception("SQL schema file is empty.");
    }

    // 3. Execute queries
    $pdo->exec($sql_content);
    $output_logs[] = "Executed database/campusflow.sql successfully.";

    // 4. Select campusflow DB to verify tables
    $pdo->exec("USE `campusflow`");
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $output_logs[] = "Database 'campusflow' created with " . count($tables) . " tables:";
    foreach ($tables as $t) {
        $output_logs[] = "  • Table: {$t}";
    }

    // 5. Verify seed users
    $ustmt = $pdo->query("SELECT id, username, role FROM users");
    $users = $ustmt->fetchAll(PDO::FETCH_ASSOC);
    $output_logs[] = "Seeded " . count($users) . " default testing accounts.";

} catch (Exception $e) {
    $success = false;
    $output_logs[] = "ERROR: " . $e->getMessage();
}

if (php_sapi_name() === 'cli') {
    foreach ($output_logs as $log) {
        echo $log . "\n";
    }
    exit($success ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup – CampusFlow</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body style="background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem;">

    <div class="auth-card" style="max-width: 680px; width: 100%; background: #1e293b; border-color: #334155; color: #f8fafc;">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div style="font-size: 3rem; margin-bottom: 0.5rem;"><?= $success ? '✅' : '❌' ?></div>
            <h1 style="font-size: 1.85rem; color: #ffffff; margin-bottom: 0.4rem;">
                <?= $success ? 'Database Initialized Successfully!' : 'Database Setup Failed' ?>
            </h1>
            <p style="color: #94a3b8; font-size: 0.95rem;">CampusFlow MySQL Database & Table Migration</p>
        </div>

        <div style="background: #0f172a; border: 1px solid #334155; border-radius: var(--radius-md); padding: 1.25rem; font-family: monospace; font-size: 0.85rem; line-height: 1.6; color: #38bdf8; max-height: 240px; overflow-y: auto; margin-bottom: 1.5rem;">
            <?php foreach ($output_logs as $log): ?>
                <div><?= htmlspecialchars($log) ?></div>
            <?php endforeach; ?>
        </div>

        <?php if ($success): ?>
            <div style="background: #064e3b; border: 1px solid #059669; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem; color: #a7f3d0; font-size: 0.9rem;">
                <strong>⚡ Ready for Testing:</strong> All 5 roles (Student, Parent, Advisor, HOD, Warden) have been seeded with password: <code>password123</code>
            </div>

            <div style="display: flex; gap: 1rem;">
                <a href="login.html" class="btn btn-primary btn-block btn-lg" style="text-align: center;">
                    🚀 Go to Login Portal →
                </a>
                <a href="index.html" class="btn btn-secondary btn-lg" style="background:#0f172a; color:#fff; border-color:#475569;">
                    Landing Page
                </a>
            </div>
        <?php else: ?>
            <div style="background: #7f1d1d; border: 1px solid #dc2626; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem; color: #fecaca; font-size: 0.9rem;">
                <strong>Troubleshooting:</strong> Please ensure MySQL is running in XAMPP on port 3306.
            </div>
            <a href="setup.php" class="btn btn-primary btn-block btn-lg" style="text-align: center;">
                🔄 Retry Database Setup
            </a>
        <?php endif; ?>
    </div>

</body>
</html>
