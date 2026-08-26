<?php
/**
 * CampusFlow - HOD Approval & Hostel Branching Dispatcher
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_middleware.php';

$auth_data = require_auth('hod');
$user      = $auth_data['user'];
$hod       = $auth_data['profile'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Invalid request method. POST expected.'], 405);
}

$leave_id = (int)($_POST['leave_id'] ?? 0);
$action   = strtolower(trim($_POST['action'] ?? ''));
$remarks  = trim($_POST['remarks'] ?? '');

if ($leave_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    send_json(['success' => false, 'message' => 'Invalid leave request ID or action.'], 400);
}

// Fetch leave request and student info
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

if ($leave['status'] !== 'Advisor Approved' && $leave['status'] !== 'Waiting for HOD') {
    send_json(['success' => false, 'message' => 'Cannot process request: Leave must have Advisor Approval first.'], 400);
}

try {
    $pdo->beginTransaction();

    if ($action === 'approve') {
        $is_hosteller = strtolower($leave['hostel_status']) === 'hosteller';

        if ($is_hosteller) {
            // Student is a hosteller -> Route to Warden
            $new_status    = 'Waiting for Warden';
            $current_stage = 'warden';
            $msg_text      = 'HOD approved. Request forwarded to Hostel Warden for final gate pass.';
        } else {
            // Student is a Day Scholar -> Workflow Completes!
            $new_status    = 'Completed';
            $current_stage = 'completed';
            $msg_text      = 'HOD approved. Leave workflow completed successfully for Day Scholar.';
        }

        $u_stmt = $pdo->prepare("
            UPDATE leave_requests 
            SET status = ?, current_stage = ?, hod_verified_at = NOW()
            WHERE id = ?
        ");
        $u_stmt->execute([$new_status, $current_stage, $leave_id]);

        $app_stmt = $pdo->prepare("
            INSERT INTO approvals (leave_id, approver_user_id, approver_role, action, remarks)
            VALUES (?, ?, 'hod', 'approved', ?)
        ");
        $app_stmt->execute([$leave_id, (int)$user['id'], $remarks ?: 'Approved by Head of the Department']);

        if ($is_hosteller) {
            // Notify Warden
            $w_stmt = $pdo->query("SELECT user_id FROM faculty WHERE role_type = 'warden' LIMIT 1");
            $warden_user = $w_stmt->fetch();
            if ($warden_user) {
                create_notification(
                    $pdo,
                    (int)$warden_user['user_id'],
                    'Hosteller Leave Pending Warden Approval',
                    "Student {$leave['student_name']} ({$leave['register_number']}) leave approved by HOD and requires Warden clearance.",
                    "warden/dashboard.php?leave_id={$leave_id}"
                );
            }

            // Notify Student
            create_notification(
                $pdo,
                (int)$leave['student_user_id'],
                'HOD Approved (Forwarded to Warden)',
                "HOD {$hod['full_name']} has approved your leave. Forwarded to Hostel Warden for final outpass.",
                "student/dashboard.php?leave_id={$leave_id}"
            );
        } else {
            // Notify Student that leave is fully completed
            create_notification(
                $pdo,
                (int)$leave['student_user_id'],
                'Leave Request Fully Approved & Completed',
                "Your leave request has received final authorization by HOD {$hod['full_name']} and is now COMPLETED.",
                "student/dashboard.php?leave_id={$leave_id}"
            );
        }

        $pdo->commit();
        send_json([
            'success'       => true,
            'message'       => $msg_text,
            'new_status'    => $new_status,
            'hostel_status' => $leave['hostel_status'],
            'is_hosteller'  => $is_hosteller
        ]);

    } else {
        // HOD Rejected
        $new_status    = 'HOD Rejected';
        $current_stage = 'rejected';

        $u_stmt = $pdo->prepare("
            UPDATE leave_requests 
            SET status = ?, current_stage = ?, hod_verified_at = NOW()
            WHERE id = ?
        ");
        $u_stmt->execute([$new_status, $current_stage, $leave_id]);

        $app_stmt = $pdo->prepare("
            INSERT INTO approvals (leave_id, approver_user_id, approver_role, action, remarks)
            VALUES (?, ?, 'hod', 'rejected', ?)
        ");
        $app_stmt->execute([$leave_id, (int)$user['id'], $remarks ?: 'Rejected by Head of the Department']);

        // Notify Student
        create_notification(
            $pdo,
            (int)$leave['student_user_id'],
            'Leave Request Rejected by HOD',
            "Your leave request was rejected by HOD {$hod['full_name']}: " . ($remarks ?: 'No remarks provided.'),
            "student/dashboard.php?leave_id={$leave_id}"
        );

        $pdo->commit();
        send_json([
            'success'    => true,
            'message'    => 'Leave request rejected by HOD.',
            'new_status' => $new_status
        ]);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_json(['success' => false, 'message' => 'HOD action error: ' . $e->getMessage()], 500);
}
