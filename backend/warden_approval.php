<?php
/**
 * CampusFlow - Hostel Warden Approval & Outpass Finalizer
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_middleware.php';

$auth_data = require_auth('warden');
$user      = $auth_data['user'];
$warden    = $auth_data['profile'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Invalid request method. POST expected.'], 405);
}

$leave_id = (int)($_POST['leave_id'] ?? 0);
$action   = strtolower(trim($_POST['action'] ?? ''));
$remarks  = trim($_POST['remarks'] ?? '');

if ($leave_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    send_json(['success' => false, 'message' => 'Invalid leave request ID or action.'], 400);
}

// Fetch leave request and verify hosteller status
$stmt = $pdo->prepare("
    SELECT lr.*, s.register_number, s.full_name AS student_name, s.department, s.hostel_status, s.user_id AS student_user_id
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.id
    WHERE lr.id = ?
");
$stmt->execute([$leave_id]);
$leave = $stmt->fetch();

if (!$leave) {
    send_json(['success' => false, 'message' => 'Leave request not found.'], 404);
}

if (strtolower($leave['hostel_status']) !== 'hosteller') {
    send_json(['success' => false, 'message' => 'Only hosteller leave requests are routed to Warden.'], 400);
}

if ($leave['status'] !== 'Waiting for Warden' && $leave['status'] !== 'HOD Approved') {
    send_json(['success' => false, 'message' => 'Cannot process: Leave must be pre-approved by Parent, Advisor, and HOD.'], 400);
}

try {
    $pdo->beginTransaction();

    if ($action === 'approve') {
        $new_status    = 'Completed';
        $current_stage = 'completed';

        $u_stmt = $pdo->prepare("
            UPDATE leave_requests 
            SET status = ?, current_stage = ?, warden_verified_at = NOW()
            WHERE id = ?
        ");
        $u_stmt->execute([$new_status, $current_stage, $leave_id]);

        $app_stmt = $pdo->prepare("
            INSERT INTO approvals (leave_id, approver_user_id, approver_role, action, remarks)
            VALUES (?, ?, 'warden', 'approved', ?)
        ");
        $app_stmt->execute([$leave_id, (int)$user['id'], $remarks ?: 'Hostel Outpass & Leave Approved by Warden']);

        // Notify Student
        create_notification(
            $pdo,
            (int)$leave['student_user_id'],
            'Hostel Leave & Outpass Completed!',
            "Warden {$warden['full_name']} has granted final approval. Your leave request is now COMPLETED.",
            "student/dashboard.php?leave_id={$leave_id}"
        );

        $pdo->commit();
        send_json([
            'success'    => true,
            'message'    => 'Hostel Outpass approved! Leave workflow is now officially Completed.',
            'new_status' => $new_status
        ]);

    } else {
        // Warden Rejected
        $new_status    = 'Warden Rejected';
        $current_stage = 'rejected';

        $u_stmt = $pdo->prepare("
            UPDATE leave_requests 
            SET status = ?, current_stage = ?, warden_verified_at = NOW()
            WHERE id = ?
        ");
        $u_stmt->execute([$new_status, $current_stage, $leave_id]);

        $app_stmt = $pdo->prepare("
            INSERT INTO approvals (leave_id, approver_user_id, approver_role, action, remarks)
            VALUES (?, ?, 'warden', 'rejected', ?)
        ");
        $app_stmt->execute([$leave_id, (int)$user['id'], $remarks ?: 'Hostel Outpass Rejected by Warden']);

        // Notify Student
        create_notification(
            $pdo,
            (int)$leave['student_user_id'],
            'Hostel Outpass Rejected by Warden',
            "Your leave outpass was rejected by Hostel Warden: " . ($remarks ?: 'No remarks provided.'),
            "student/dashboard.php?leave_id={$leave_id}"
        );

        $pdo->commit();
        send_json([
            'success'    => true,
            'message'    => 'Hostel outpass rejected by Warden.',
            'new_status' => $new_status
        ]);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_json(['success' => false, 'message' => 'Warden action error: ' . $e->getMessage()], 500);
}
