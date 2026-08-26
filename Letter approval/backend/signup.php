<?php
/**
 * CampusFlow - Multi-Role Dynamic Signup API Endpoint
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Invalid request method. POST expected.'], 405);
}

$role = trim($_POST['role'] ?? '');
$valid_roles = ['student', 'parent', 'advisor', 'hod', 'warden'];

if (!in_array($role, $valid_roles, true)) {
    send_json(['success' => false, 'message' => 'Please select a valid role.'], 400);
}

$password = $_POST['password'] ?? '';
if (strlen($password) < 6) {
    send_json(['success' => false, 'message' => 'Password must be at least 6 characters long.'], 400);
}
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    if ($role === 'student') {
        $full_name       = trim($_POST['full_name'] ?? '');
        $register_number = strtoupper(trim($_POST['register_number'] ?? ''));
        $email           = strtolower(trim($_POST['email'] ?? ''));
        $phone           = trim($_POST['phone'] ?? '');
        $department      = trim($_POST['department'] ?? '');
        $year            = trim($_POST['year'] ?? '1');
        $section         = strtoupper(trim($_POST['section'] ?? 'A'));
        $hostel_status   = strtolower(trim($_POST['hostel_status'] ?? 'day_scholar'));
        $parent_phone    = trim($_POST['parent_phone'] ?? '');

        if (empty($full_name) || empty($register_number) || empty($email) || empty($phone) || empty($department)) {
            send_json(['success' => false, 'message' => 'Please fill in all mandatory student fields.'], 400);
        }

        if (!in_array($hostel_status, ['hosteller', 'day_scholar'], true)) {
            $hostel_status = 'day_scholar';
        }

        // Check if username/reg number or email already exists
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $chk->execute([$register_number, $email]);
        if ($chk->fetch()) {
            send_json(['success' => false, 'message' => 'A user with this Student Register Number or Email already exists.'], 409);
        }

        // Insert into users
        $u_stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'student')");
        $u_stmt->execute([$register_number, $email, $phone, $password_hash]);
        $user_id = (int)$pdo->lastInsertId();

        // Check if a parent with matching student_reg_no or parent_phone exists to auto-link
        $parent_id = null;
        if (!empty($parent_phone)) {
            $p_find = $pdo->prepare("SELECT id FROM parents WHERE phone = ? OR student_reg_no = ? LIMIT 1");
            $p_find->execute([$parent_phone, $register_number]);
            $p_row = $p_find->fetch();
            if ($p_row) {
                $parent_id = (int)$p_row['id'];
            }
        }

        // Insert into students
        $room_number = ($hostel_status === 'hosteller') ? 'BH-204' : null;
        $s_stmt = $pdo->prepare("
            INSERT INTO students (user_id, register_number, full_name, department, year, section, hostel_status, room_number, parent_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $s_stmt->execute([$user_id, $register_number, $full_name, $department, (int)$year, $section, $hostel_status, $room_number, $parent_id]);

        $pdo->commit();
        send_json([
            'success' => true,
            'message' => 'Student registration successful! You can now log in.',
            'redirect' => 'login.html?registered=student'
        ]);

    } elseif ($role === 'parent') {
        $full_name          = trim($_POST['full_name'] ?? '');
        $phone              = trim($_POST['phone'] ?? '');
        $student_reg_no     = strtoupper(trim($_POST['student_reg_no'] ?? ''));
        $preferred_language = trim($_POST['preferred_language'] ?? 'ta');
        $voice_data         = $_POST['voice_sample_data'] ?? null; // base64 audio or wav

        if (empty($full_name) || empty($phone) || empty($student_reg_no)) {
            send_json(['success' => false, 'message' => 'Parent Name, Phone Number, and Child Register Number are required.'], 400);
        }

        // Username is phone number for parents
        $u_stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'parent')");
        $u_stmt->execute([$phone, $email, $phone, $password_hash]);
        $user_id = (int)$pdo->lastInsertId();

        $voice_enrolled = !empty($voice_data) ? 1 : 0;

        // Insert into parents
        $p_stmt = $pdo->prepare("
            INSERT INTO parents (user_id, full_name, phone, email, student_reg_no, preferred_language, voice_enrolled)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $p_stmt->execute([$user_id, $full_name, $phone, $email, $student_reg_no, $preferred_language, $voice_enrolled]);
        $parent_id = (int)$pdo->lastInsertId();

        // Handle Voice Sample Storage
        if (!empty($voice_data)) {
            $upload_dir = __DIR__ . '/../assets/uploads/voice_samples/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = 'sample_parent_' . $parent_id . '_' . time() . '.wav';
            $file_path = $upload_dir . $filename;
            $relative_path = 'assets/uploads/voice_samples/' . $filename;

            // If base64 encoded audio
            if (str_starts_with($voice_data, 'data:audio')) {
                $parts = explode(',', $voice_data);
                $audio_bytes = base64_decode(end($parts));
                file_put_contents($file_path, $audio_bytes);
            } else {
                file_put_contents($file_path, $voice_data);
            }

            $features = json_encode([
                'sample_rate' => 44100,
                'created_at'  => date('Y-m-d H:i:s'),
                'size_bytes'  => file_exists($file_path) ? filesize($file_path) : 0
            ]);

            $v_stmt = $pdo->prepare("
                INSERT INTO voice_samples (parent_id, audio_path, passphrase_text, audio_features_json, sample_hash)
                VALUES (?, ?, ?, ?, ?)
            ");
            $v_stmt->execute([
                $parent_id,
                $relative_path,
                'I hereby approve leave for my ward ' . $student_reg_no,
                $features,
                md5($filename)
            ]);
        }

        // Link with student if student already exists
        $link_stmt = $pdo->prepare("UPDATE students SET parent_id = ? WHERE register_number = ?");
        $link_stmt->execute([$parent_id, $student_reg_no]);

        $pdo->commit();
        send_json([
            'success' => true,
            'message' => 'Parent registration complete with voice biometric profile! You can now log in.',
            'redirect' => 'login.html?registered=parent'
        ]);

    } elseif ($role === 'advisor') {
        $full_name       = trim($_POST['full_name'] ?? '');
        $faculty_id_no   = strtoupper(trim($_POST['faculty_id_no'] ?? ''));
        $email           = strtolower(trim($_POST['email'] ?? ''));
        $phone           = trim($_POST['phone'] ?? '');
        $department      = trim($_POST['department'] ?? '');
        $section_handled = trim($_POST['section_handled'] ?? '');

        if (empty($full_name) || empty($faculty_id_no) || empty($email) || empty($phone) || empty($department)) {
            send_json(['success' => false, 'message' => 'Please fill in all Class Advisor fields.'], 400);
        }

        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $chk->execute([$faculty_id_no, $email]);
        if ($chk->fetch()) {
            send_json(['success' => false, 'message' => 'A faculty account with this ID or Email already exists.'], 409);
        }

        $u_stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'advisor')");
        $u_stmt->execute([$faculty_id_no, $email, $phone, $password_hash]);
        $user_id = (int)$pdo->lastInsertId();

        $f_stmt = $pdo->prepare("
            INSERT INTO faculty (user_id, faculty_id_no, full_name, email, phone, role_type, department, section_handled)
            VALUES (?, ?, ?, ?, ?, 'advisor', ?, ?)
        ");
        $f_stmt->execute([$user_id, $faculty_id_no, $full_name, $email, $phone, $department, $section_handled]);

        $pdo->commit();
        send_json([
            'success' => true,
            'message' => 'Class Advisor registration successful! Please log in.',
            'redirect' => 'login.html?registered=advisor'
        ]);

    } elseif ($role === 'hod') {
        $full_name     = trim($_POST['full_name'] ?? '');
        $faculty_id_no = strtoupper(trim($_POST['faculty_id_no'] ?? ''));
        $email         = strtolower(trim($_POST['email'] ?? ''));
        $phone         = trim($_POST['phone'] ?? '');
        $department    = trim($_POST['department'] ?? '');

        if (empty($full_name) || empty($faculty_id_no) || empty($email) || empty($phone) || empty($department)) {
            send_json(['success' => false, 'message' => 'Please fill in all HOD details.'], 400);
        }

        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $chk->execute([$faculty_id_no, $email]);
        if ($chk->fetch()) {
            send_json(['success' => false, 'message' => 'An HOD account with this Faculty ID or Email already exists.'], 409);
        }

        $u_stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'hod')");
        $u_stmt->execute([$faculty_id_no, $email, $phone, $password_hash]);
        $user_id = (int)$pdo->lastInsertId();

        $f_stmt = $pdo->prepare("
            INSERT INTO faculty (user_id, faculty_id_no, full_name, email, phone, role_type, department)
            VALUES (?, ?, ?, ?, ?, 'hod', ?)
        ");
        $f_stmt->execute([$user_id, $faculty_id_no, $full_name, $email, $phone, $department]);

        $pdo->commit();
        send_json([
            'success' => true,
            'message' => 'HOD registration successful! Please log in.',
            'redirect' => 'login.html?registered=hod'
        ]);

    } elseif ($role === 'warden') {
        $full_name     = trim($_POST['full_name'] ?? '');
        $faculty_id_no = strtoupper(trim($_POST['faculty_id_no'] ?? ''));
        $email         = strtolower(trim($_POST['email'] ?? ''));
        $phone         = trim($_POST['phone'] ?? '');
        $hostel_name   = trim($_POST['hostel_name'] ?? '');

        if (empty($full_name) || empty($faculty_id_no) || empty($email) || empty($phone) || empty($hostel_name)) {
            send_json(['success' => false, 'message' => 'Please fill in all Warden and Hostel details.'], 400);
        }

        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $chk->execute([$faculty_id_no, $email]);
        if ($chk->fetch()) {
            send_json(['success' => false, 'message' => 'A Warden account with this ID or Email already exists.'], 409);
        }

        $u_stmt = $pdo->prepare("INSERT INTO users (username, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'warden')");
        $u_stmt->execute([$faculty_id_no, $email, $phone, $password_hash]);
        $user_id = (int)$pdo->lastInsertId();

        $f_stmt = $pdo->prepare("
            INSERT INTO faculty (user_id, faculty_id_no, full_name, email, phone, role_type, hostel_name)
            VALUES (?, ?, ?, ?, ?, 'warden', ?)
        ");
        $f_stmt->execute([$user_id, $faculty_id_no, $full_name, $email, $phone, $hostel_name]);

        $pdo->commit();
        send_json([
            'success' => true,
            'message' => 'Hostel Warden registration successful! Please log in.',
            'redirect' => 'login.html?registered=warden'
        ]);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    send_json([
        'success' => false,
        'message' => 'Registration error: ' . $e->getMessage()
    ], 500);
}
