<?php
/**
 * CampusFlow - Login Authentication & Dashboard Routing Handler
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Invalid request method. POST expected.'], 405);
}

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';
$role_hint  = trim($_POST['role'] ?? '');

if (empty($identifier) || empty($password)) {
    send_json(['success' => false, 'message' => 'Please provide both user identifier and password.'], 400);
}

try {
    // Find user by username, email, or phone
    $query = "SELECT * FROM users WHERE username = ? OR email = ? OR phone = ?";
    $params = [$identifier, $identifier, $identifier];

    if (!empty($role_hint)) {
        $query .= " AND role = ?";
        $params[] = $role_hint;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $user = $stmt->fetch();

    if (!$user) {
        send_json(['success' => false, 'message' => 'Invalid login credentials. User not found.'], 401);
    }

    if (!password_verify($password, $user['password_hash'])) {
        send_json(['success' => false, 'message' => 'Invalid password. Please try again.'], 401);
    }

    // Set Session Variables
    $_SESSION['user_id']  = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['email']    = $user['email'];

    // Determine target dashboard path
    $dashboard_map = [
        'student' => 'student/dashboard.php',
        'parent'  => 'parent/dashboard.php',
        'advisor' => 'advisor/dashboard.php',
        'hod'     => 'hod/dashboard.php',
        'warden'  => 'warden/dashboard.php'
    ];

    $redirect_url = $dashboard_map[$user['role']] ?? 'index.html';

    send_json([
        'success'      => true,
        'message'      => 'Login successful! Redirecting to your dashboard...',
        'role'         => $user['role'],
        'redirect_url' => $redirect_url
    ]);

} catch (Exception $e) {
    send_json([
        'success' => false,
        'message' => 'Login error: ' . $e->getMessage()
    ], 500);
}
