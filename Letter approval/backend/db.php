<?php
/**
 * CampusFlow - Database Connection Handler (PDO)
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

require_once __DIR__ . '/supabase.php';

$supabase_cfg = SupabaseClient::loadConfig();

$db_host = '127.0.0.1';
$db_port = '3306';
$db_name = 'campusflow';
$db_user = 'root';
$db_pass = ''; // default xampp password

try {
    if (!empty($supabase_cfg['enabled']) && !empty($supabase_cfg['db_host']) && extension_loaded('pdo_pgsql')) {
        // Connect to Supabase PostgreSQL Database Cluster
        $pg_host = $supabase_cfg['db_host'];
        $pg_port = $supabase_cfg['db_port'] ?? '5432';
        $pg_db   = $supabase_cfg['db_name'] ?? 'postgres';
        $pg_user = $supabase_cfg['db_user'] ?? 'postgres';
        $pg_pass = $supabase_cfg['db_pass'] ?? '';

        $dsn = "pgsql:host={$pg_host};port={$pg_port};dbname={$pg_db}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];
        $pdo = new PDO($dsn, $pg_user, $pg_pass, $options);
    } else {
        // Connect to local database
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    }
} catch (PDOException $e) {
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Database connection failed: " . $e->getMessage() . "\n");
    } else {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed. Check your database configuration or Supabase settings.',
            'error'   => $e->getMessage()
        ]);
        exit;
    }
}

/**
 * Send JSON response and exit
 */
function send_json(array $data, int $status_code = 200): void {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Log Notification Helper
 */
function create_notification(PDO $pdo, int $user_id, string $title, string $message, ?string $action_url = null): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, action_url) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $title, $message, $action_url]);
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}
