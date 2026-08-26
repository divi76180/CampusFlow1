<?php
/**
 * CampusFlow - Authentication & Role-Based Access Control (RBAC) Middleware
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Check if the user is authenticated
 */
function is_authenticated(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Require authentication or redirect/respond with error
 */
function require_auth(?string $required_role = null): array {
    global $pdo;

    if (!is_authenticated()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            send_json(['success' => false, 'message' => 'Unauthorized access. Please log in.'], 401);
        }
        header("Location: ../login.html?error=unauthorized");
        exit;
    }

    $current_role = $_SESSION['role'];

    if ($required_role !== null && $current_role !== $required_role) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            send_json(['success' => false, 'message' => 'Forbidden: Insufficient privileges.'], 403);
        }
        // Redirect to their own dashboard
        $redirect_map = [
            'student' => '../student/dashboard.php',
            'parent'  => '../parent/dashboard.php',
            'advisor' => '../advisor/dashboard.php',
            'hod'     => '../hod/dashboard.php',
            'warden'  => '../warden/dashboard.php',
        ];
        $dest = $redirect_map[$current_role] ?? '../login.html';
        header("Location: " . $dest);
        exit;
    }

    // Fetch live user & profile data
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT id, username, email, phone, role, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        session_unset();
        session_destroy();
        header("Location: ../login.html?error=invalid_session");
        exit;
    }

    $profile = null;
    if ($current_role === 'student') {
        $pstmt = $pdo->prepare("
            SELECT s.*, p.full_name AS parent_name, p.phone AS parent_phone, p.email AS parent_email, p.preferred_language
            FROM students s
            LEFT JOIN parents p ON s.parent_id = p.id
            WHERE s.user_id = ?
        ");
        $pstmt->execute([$user_id]);
        $profile = $pstmt->fetch();
    } elseif ($current_role === 'parent') {
        $pstmt = $pdo->prepare("
            SELECT p.*, s.id AS student_id, s.full_name AS student_name, s.department, s.year, s.section, s.hostel_status
            FROM parents p
            LEFT JOIN students s ON s.register_number = p.student_reg_no
            WHERE p.user_id = ?
        ");
        $pstmt->execute([$user_id]);
        $profile = $pstmt->fetch();
    } elseif (in_array($current_role, ['advisor', 'hod', 'warden'], true)) {
        $pstmt = $pdo->prepare("SELECT * FROM faculty WHERE user_id = ?");
        $pstmt->execute([$user_id]);
        $profile = $pstmt->fetch();
    }

    return [
        'user'    => $user,
        'profile' => $profile
    ];
}
