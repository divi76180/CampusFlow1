<?php
/**
 * CampusFlow - Leave Request Controller & API
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_middleware.php';

$auth_data = require_auth();
$user      = $auth_data['user'];
$profile   = $auth_data['profile'];

// Handle GET Requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'get_my_leaves';

    if ($action === 'get_my_leaves') {
        if ($user['role'] !== 'student') {
            send_json(['success' => false, 'message' => 'Unauthorized role.'], 403);
        }

        $stmt = $pdo->prepare("
            SELECT lr.*, s.register_number, s.full_name AS student_name, s.hostel_status, s.department
            FROM leave_requests lr
            JOIN students s ON lr.student_id = s.id
            WHERE s.user_id = ?
            ORDER BY lr.created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $leaves = $stmt->fetchAll();

        send_json(['success' => true, 'leaves' => $leaves]);
    }

    if ($action === 'get_leave_details') {
        $leave_id = (int)($_GET['id'] ?? 0);
        if ($leave_id <= 0) {
            send_json(['success' => false, 'message' => 'Invalid leave request ID.'], 400);
        }

        $stmt = $pdo->prepare("
            SELECT lr.*, 
                   s.register_number, s.full_name AS student_name, s.department, s.year, s.section, s.hostel_status, s.phone AS student_phone, s.email AS student_email,
                   p.full_name AS parent_name, p.phone AS parent_phone, p.email AS parent_email, p.preferred_language
            FROM leave_requests lr
            JOIN students s ON lr.student_id = s.id
            LEFT JOIN parents p ON s.parent_id = p.id
            WHERE lr.id = ?
        ");
        $stmt->execute([$leave_id]);
        $leave = $stmt->fetch();

        if (!$leave) {
            send_json(['success' => false, 'message' => 'Leave request not found.'], 404);
        }

        // Fetch approval timeline
        $astmt = $pdo->prepare("
            SELECT a.*, u.username, u.role,
                   CASE 
                       WHEN a.approver_role = 'parent' THEN p.full_name
                       WHEN a.approver_role IN ('advisor', 'hod', 'warden') THEN f.full_name
                       ELSE u.username
                   END AS approver_name
            FROM approvals a
            JOIN users u ON a.approver_user_id = u.id
            LEFT JOIN parents p ON p.user_id = u.id
            LEFT JOIN faculty f ON f.user_id = u.id
            WHERE a.leave_id = ?
            ORDER BY a.action_timestamp ASC
        ");
        $astmt->execute([$leave_id]);
        $approvals = $astmt->fetchAll();

        // Fetch voice verification details if available
        $vstmt = $pdo->prepare("SELECT * FROM voice_verifications WHERE leave_id = ? ORDER BY verified_at DESC LIMIT 1");
        $vstmt->execute([$leave_id]);
        $voice_verif = $vstmt->fetch();

        send_json([
            'success'            => true,
            'leave'              => $leave,
            'approvals'          => $approvals,
            'voice_verification' => $voice_verif
        ]);
    }
}

// Handle POST Requests (Create Leave Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user['role'] !== 'student') {
        send_json(['success' => false, 'message' => 'Only registered students can apply for leave.'], 403);
    }

    $student_id = (int)($profile['id'] ?? 0);
    if ($student_id <= 0) {
        send_json(['success' => false, 'message' => 'Student profile record missing.'], 400);
    }

    $leave_type          = trim($_POST['leave_type'] ?? 'Casual / Home Visit');
    $from_date           = trim($_POST['from_date'] ?? '');
    $to_date             = trim($_POST['to_date'] ?? '');
    $from_time           = !empty($_POST['from_time']) ? trim($_POST['from_time']) : '08:00:00';
    $to_time             = !empty($_POST['to_time']) ? trim($_POST['to_time']) : '18:00:00';
    $reason              = trim($_POST['reason'] ?? '');
    $destination_address = trim($_POST['destination_address'] ?? '');
    $emergency_contact   = trim($_POST['emergency_contact'] ?? ($profile['phone'] ?? ''));

    if (empty($from_date) || empty($to_date) || empty($reason) || empty($destination_address)) {
        send_json(['success' => false, 'message' => 'Please fill in all mandatory leave details.'], 400);
    }

    if (strtotime($from_date) > strtotime($to_date)) {
        send_json(['success' => false, 'message' => 'Leave start date cannot be after the end date.'], 400);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO leave_requests (
                student_id, leave_type, from_date, to_date, from_time, to_time,
                reason, destination_address, emergency_contact, status, current_stage
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Waiting for Parent', 'parent')
        ");
        $stmt->execute([
            $student_id, $leave_type, $from_date, $to_date, $from_time, $to_time,
            $reason, $destination_address, $emergency_contact
        ]);
        $leave_id = (int)$pdo->lastInsertId();

        // Notify parent if linked
        if (!empty($profile['parent_id'])) {
            $p_user_stmt = $pdo->prepare("SELECT user_id FROM parents WHERE id = ?");
            $p_user_stmt->execute([$profile['parent_id']]);
            $p_user = $p_user_stmt->fetch();
            if ($p_user) {
                create_notification(
                    $pdo,
                    (int)$p_user['user_id'],
                    'New Leave Request Submitted',
                    "Your ward {$profile['full_name']} has applied for {$leave_type} from {$from_date} to {$to_date}. Please review and approve with voice verification.",
                    "parent/dashboard.php?leave_id={$leave_id}"
                );
            }
        }

        $pdo->commit();
        send_json([
            'success'  => true,
            'message'  => 'Leave request submitted successfully! Forwarded to Parent for voice approval.',
            'leave_id' => $leave_id
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        send_json(['success' => false, 'message' => 'Failed to submit leave request: ' . $e->getMessage()], 500);
    }
}
