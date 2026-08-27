<?php
/**
 * CampusFlow - Class Advisor Approval & HOD Forwarding Handler
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_middleware.php';

$auth_data = require_auth('advisor');
$user      = $auth_data['user'];
$advisor   = $auth_data['profile'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Invalid request method. POST expected.'], 405);
}

$leave_id = (int)($_POST['leave_id'] ?? 0);
$action   = strtolower(trim($_POST['action'] ?? ''));
$remarks  = trim($_POST['remarks'] ?? '');

if ($leave_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    send_json(['success' => false, 'message' => 'Invalid leave ID or action.'], 400);
}

// Fetch leave request and verify parent approval status
$stmt = $pdo->prepare("
    SELECT lr.*, s.register_number, s.full_name AS student_name, s.department, s.section, s.user_id AS student_user_id
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.id
    WHERE lr.id = ?
");
$stmt->execute([$leave_id]);
$leave = $stmt->fetch();

if (!$leave) {
    send_json(['success' => false, 'message' => 'Leave request not found.'], 404);
}

if ($leave['status'] !== 'Parent Approved' && $leave['status'] !== 'Waiting for Class Advisor') {
    send_json(['success' => false, 'message' => 'Cannot process request: Leave must have verified Parent Approval first.'], 400);
}

try {
    $pdo->beginTransaction();

    if ($action === 'approve') {
        $new_status    = 'Advisor Approved';
        $current_stage = 'hod';

        $u_stmt = $pdo->prepare("
            UPDATE leave_requests 
            SET status = ?, current_stage = ?, advisor_verified_at = NOW()
            WHERE id = ?
        ");
        $u_stmt->execute([$new_status, $current_stage, $leave_id]);

        $app_stmt = $pdo->prepare("
            INSERT INTO approvals (leave_id, approver_user_id, approver_role, action, remarks)
            VALUES (?, ?, 'advisor', 'approved', ?)
        ");
        $app_stmt->execute([$leave_id, (int)$user['id'], $remarks ?: 'Approved by Class Advisor']);

        // Notify HOD of the department
        $hod_stmt = $pdo->prepare("
            SELECT user_id FROM faculty 
            WHERE role_type = 'hod' AND department = ?
            LIMIT 1
        ");
        $hod_stmt->execute([$leave['department']]);
        $hod_user = $hod_stmt->fetch();

        if ($hod_user) {
            create_notification(
                $pdo,
                (int)$hod_user['user_id'],
                'Leave Request Pending HOD Approval',
                "Advisor has approved leave for {$leave['student_name']} ({$leave['register_number']}). Pending your final department authorization.",
                "hod/dashboard.php?leave_id={$leave_id}"
            );
        }

        // Notify Student
        create_notification(
            $pdo,
            (int)$leave['student_user_id'],
            'Advisor Approved Leave Request',
            "Your Class Advisor {$advisor['full_name']} has approved your leave. It has now progressed to the HOD.",
            "student/dashboard.php?leave_id={$leave_id}"
        );

        $pdo->commit();
        send_json([
            'success'    => true,
            'message'    => 'Leave request approved successfully and forwarded to HOD.',
            'new_status' => $new_status
        ]);

    } else {
        // Reject
        $new_status    = 'Advisor Rejected';
        $current_stage = 'rejected';

        $u_stmt = $pdo->prepare("
            UPDATE leave_requests 
            SET status = ?, current_stage = ?, advisor_verified_at = NOW()
            WHERE id = ?
        ");
        $u_stmt->execute([$new_status, $current_stage, $leave_id]);

        $app_stmt = $pdo->prepare("
            INSERT INTO approvals (leave_id, approver_user_id, approver_role, action, remarks)
            VALUES (?, ?, 'advisor', 'rejected', ?)
        ");
        $app_stmt->execute([$leave_id, (int)$user['id'], $remarks ?: 'Rejected by Class Advisor']);

        // Notify Student
        create_notification(
            $pdo,
            (int)$leave['student_user_id'],
            'Leave Request Rejected by Advisor',
            "Your Class Advisor declined your leave request: " . ($remarks ?: 'No remarks provided.'),
            "student/dashboard.php?leave_id={$leave_id}"
        );

        $pdo->commit();
        send_json([
            'success'    => true,
            'message'    => 'Leave request rejected.',
            'new_status' => $new_status
        ]);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_json(['success' => false, 'message' => 'Advisor action error: ' . $e->getMessage()], 500);
}
