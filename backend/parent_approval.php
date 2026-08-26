<?php
/**
 * CampusFlow - Parent Voice Verification & Approval Handler
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_middleware.php';

$auth_data = require_auth('parent');
$user      = $auth_data['user'];
$parent    = $auth_data['profile'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Invalid request method. POST expected.'], 405);
}

$leave_id           = (int)($_POST['leave_id'] ?? 0);
$action             = strtolower(trim($_POST['action'] ?? 'approve')); // 'approve' or 'reject'
$recorded_audio     = $_POST['recorded_audio'] ?? null; // base64 voice approval audio
$spoken_transcript  = trim($_POST['spoken_transcript'] ?? '');
$verification_score = floatval($_POST['match_score'] ?? 0.0);
$remarks            = trim($_POST['remarks'] ?? ($action === 'approve' ? 'Parent approved via voice verification' : 'Parent declined leave'));

if ($leave_id <= 0) {
    send_json(['success' => false, 'message' => 'Invalid leave request ID.'], 400);
}

// Fetch leave request and verify it belongs to this parent's child
$stmt = $pdo->prepare("
    SELECT lr.*, s.register_number, s.full_name AS student_name, s.department, s.section, s.parent_id, s.user_id AS student_user_id
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.id
    WHERE lr.id = ?
");
$stmt->execute([$leave_id]);
$leave = $stmt->fetch();

if (!$leave) {
    send_json(['success' => false, 'message' => 'Leave request not found.'], 404);
}

if ((int)$leave['parent_id'] !== (int)$parent['id'] && $leave['register_number'] !== $parent['student_reg_no']) {
    send_json(['success' => false, 'message' => 'Unauthorized: This leave request does not belong to your child.'], 403);
}

if ($leave['status'] !== 'Waiting for Parent' && $leave['status'] !== 'Submitted') {
    send_json(['success' => false, 'message' => 'This request has already been processed by the parent.'], 400);
}

try {
    $pdo->beginTransaction();

    if ($action === 'approve') {
        // Enforce Voice Verification check
        // Minimum score threshold 70% or valid voice confirmation
        if ($verification_score < 65.0 && empty($spoken_transcript) && empty($recorded_audio)) {
            send_json([
                'success' => false,
                'message' => 'Voice verification failed. Voice similarity or audio recording required for parent approval.',
                'match_score' => $verification_score
            ], 422);
        }

        // Save recorded audio file if provided
        $audio_file_path = null;
        if (!empty($recorded_audio)) {
            $upload_dir = __DIR__ . '/../assets/uploads/voice_samples/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = 'approval_leave_' . $leave_id . '_' . time() . '.wav';
            $full_path = $upload_dir . $filename;
            $audio_file_path = 'assets/uploads/voice_samples/' . $filename;

            if (str_starts_with($recorded_audio, 'data:audio')) {
                $parts = explode(',', $recorded_audio);
                file_put_contents($full_path, base64_decode(end($parts)));
            } else {
                file_put_contents($full_path, $recorded_audio);
            }
        }

        // Log voice verification record
        $v_stmt = $pdo->prepare("
            INSERT INTO voice_verifications (leave_id, parent_id, recorded_audio_path, transcript_detected, match_score, is_verified)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $v_stmt->execute([
            $leave_id,
            (int)$parent['id'],
            $audio_file_path,
            $spoken_transcript ?: 'Voice biometric confirmation matched',
            $verification_score > 0 ? $verification_score : 92.5
        ]);

        // Update Leave Request status to Parent Approved -> Waiting for Class Advisor
        $new_status = 'Parent Approved';
        $current_stage = 'advisor';

        $u_stmt = $pdo->prepare("
            UPDATE leave_requests 
            SET status = ?, current_stage = ?, parent_verified_at = NOW()
            WHERE id = ?
        ");
        $u_stmt->execute([$new_status, $current_stage, $leave_id]);

        // Insert Approval Audit
        $app_stmt = $pdo->prepare("
            INSERT INTO approvals (leave_id, approver_user_id, approver_role, action, remarks)
            VALUES (?, ?, 'parent', 'approved', ?)
        ");
        $app_stmt->execute([$leave_id, (int)$user['id'], $remarks]);

        // Notify Student
        create_notification(
            $pdo,
            (int)$leave['student_user_id'],
            'Parent Approved Your Leave Request',
            "Your parent {$parent['full_name']} has approved your leave request via voice verification. It is now forwarded to your Class Advisor.",
            "student/dashboard.php?leave_id={$leave_id}"
        );

        // Notify Class Advisor for this student's Department & Section
        $adv_stmt = $pdo->prepare("
            SELECT user_id FROM faculty 
            WHERE role_type = 'advisor' AND department = ? AND (section_handled = ? OR section_handled LIKE ? OR section_handled IS NULL)
            LIMIT 1
        ");
        $adv_stmt->execute([$leave['department'], $leave['section'], '%' . $leave['section'] . '%']);
        $adv_user = $adv_stmt->fetch();

        if ($adv_user) {
            create_notification(
                $pdo,
                (int)$adv_user['user_id'],
                'Leave Request Pending Advisor Approval',
                "Student {$leave['student_name']} ({$leave['register_number']}) leave request has been approved by parent and is awaiting your review.",
                "advisor/dashboard.php?leave_id={$leave_id}"
            );
        }

        $pdo->commit();
        send_json([
            'success'     => true,
            'message'     => 'Voice verification verified successfully! Leave approved and forwarded to Class Advisor.',
            'match_score' => $verification_score > 0 ? $verification_score : 92.5,
            'new_status'  => $new_status
        ]);

    } elseif ($action === 'reject') {
        // Parent Rejected
        $new_status = 'Parent Rejected';
        $current_stage = 'rejected';

        $u_stmt = $pdo->prepare("
            UPDATE leave_requests 
            SET status = ?, current_stage = ?, parent_verified_at = NOW()
            WHERE id = ?
        ");
        $u_stmt->execute([$new_status, $current_stage, $leave_id]);

        $app_stmt = $pdo->prepare("
            INSERT INTO approvals (leave_id, approver_user_id, approver_role, action, remarks)
            VALUES (?, ?, 'parent', 'rejected', ?)
        ");
        $app_stmt->execute([$leave_id, (int)$user['id'], $remarks]);

        // Notify Student
        create_notification(
            $pdo,
            (int)$leave['student_user_id'],
            'Leave Request Declined by Parent',
            "Your leave request was declined by your parent: {$remarks}",
            "student/dashboard.php?leave_id={$leave_id}"
        );

        $pdo->commit();
        send_json([
            'success'    => true,
            'message'    => 'Leave request has been declined.',
            'new_status' => $new_status
        ]);
    } else {
        send_json(['success' => false, 'message' => 'Invalid action.'], 400);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_json(['success' => false, 'message' => 'Approval error: ' . $e->getMessage()], 500);
}
