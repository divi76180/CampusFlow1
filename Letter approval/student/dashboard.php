<?php
/**
 * CampusFlow - Student Dashboard
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth_middleware.php';

$auth_data = require_auth('student');
$user      = $auth_data['user'];
$student   = $auth_data['profile'];

// Fetch student's leaves from DB
$stmt = $pdo->prepare("
    SELECT * FROM leave_requests 
    WHERE student_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([(int)$student['id']]);
$leaves = $stmt->fetchAll();

// Calculate metrics
$total_leaves = count($leaves);
$approved_count = 0;
$pending_count = 0;
$rejected_count = 0;

foreach ($leaves as $l) {
    $st = strtolower($l['status']);
    if (strpos($st, 'completed') !== false || strpos($st, 'approved') !== false) {
        $approved_count++;
    } elseif (strpos($st, 'rejected') !== false) {
        $rejected_count++;
    } else {
        $pending_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard – CampusFlow</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="dashboard-layout">

    <!-- Top Navigation Bar -->
    <header class="app-navbar">
        <div class="container nav-wrapper">
            <a href="../index.html" class="brand-logo">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                CampusFlow
            </a>

            <div class="user-profile-menu">
                <div class="user-badge-info">
                    <div class="user-avatar"><?= strtoupper(substr($student['full_name'] ?? 'S', 0, 1)) ?></div>
                    <div class="user-text">
                        <div class="user-name"><?= htmlspecialchars($student['full_name'] ?? 'Student') ?></div>
                        <div class="user-role-tag">Student (<?= htmlspecialchars($student['register_number'] ?? '') ?>)</div>
                    </div>
                </div>
                <a href="../backend/logout.php" class="btn btn-secondary btn-sm" style="background:#1e293b; color:#fff; border-color:#334155;">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="dashboard-content container">

        <!-- Student Profile Banner -->
        <div class="profile-banner-card">
            <div class="profile-banner-grid">
                <div class="profile-main-details">
                    <h1><?= htmlspecialchars($student['full_name'] ?? 'Student') ?></h1>
                    <p style="color: #cbd5e1; font-size: 0.95rem;">
                        <?= htmlspecialchars($student['department'] ?? '') ?> • Year <?= htmlspecialchars($student['year'] ?? '') ?> • Section <?= htmlspecialchars($student['section'] ?? '') ?>
                    </p>
                    <div class="profile-meta-tags">
                        <span class="meta-pill">🆔 Reg: <?= htmlspecialchars($student['register_number'] ?? '') ?></span>
                        <span class="meta-pill <?= $student['hostel_status'] === 'hosteller' ? 'hosteller' : 'day-scholar' ?>">
                            <?= $student['hostel_status'] === 'hosteller' ? '🏢 Hosteller (Routes to Warden)' : '🚌 Day Scholar (Direct Completion)' ?>
                        </span>
                        <span class="meta-pill">👨‍👩‍👧 Parent: <?= htmlspecialchars($student['parent_name'] ?? 'Linked Parent') ?> (<?= htmlspecialchars($student['parent_phone'] ?? 'N/A') ?>)</span>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-primary btn-lg" onclick="openModal('applyLeaveModal')">
                        ➕ Apply for Leave
                    </button>
                </div>
            </div>
        </div>

        <!-- Metric Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <span>Total Applications</span>
                    <h3><?= $total_leaves ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-blue">📋</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Pending Action</span>
                    <h3><?= $pending_count ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-yellow">⏳</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Authorized / Completed</span>
                    <h3><?= $approved_count ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-green">✅</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Declined / Rejected</span>
                    <h3><?= $rejected_count ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-red">❌</div>
            </div>
        </div>

        <!-- Leave Applications Table -->
        <div class="section-toolbar">
            <h2>Leave Request History</h2>
        </div>

        <div class="table-card">
            <?php if (empty($leaves)): ?>
                <div class="empty-state">
                    <h4>No leave applications found</h4>
                    <p>Click "Apply for Leave" above to submit your first leave application.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Category</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Destination</th>
                                <th>Current Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaves as $l): ?>
                                <tr>
                                    <td><strong>#LR-<?= $l['id'] ?></strong></td>
                                    <td><span style="font-weight: 600; color: #1e40af;"><?= htmlspecialchars($l['leave_type']) ?></span></td>
                                    <td>
                                        <?= date('d M Y', strtotime($l['from_date'])) ?> to <?= date('d M Y', strtotime($l['to_date'])) ?>
                                    </td>
                                    <td style="max-width: 250px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                        <?= htmlspecialchars($l['reason']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($l['destination_address']) ?></td>
                                    <td>
                                        <span class="status-badge <?= 
                                            (strpos(strtolower($l['status']), 'completed') !== false || strpos(strtolower($l['status']), 'approved') !== false) ? 'status-green' : 
                                            ((strpos(strtolower($l['status']), 'rejected') !== false) ? 'status-red' : 'status-yellow') 
                                        ?>">
                                            <?= htmlspecialchars($l['status']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; gap: 0.4rem; flex-wrap: wrap; justify-content: center;">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="viewLeaveLetter(<?= $l['id'] ?>)">
                                                📄 View Letter
                                            </button>
                                            <?php if ($student['hostel_status'] === 'hosteller' && (strpos(strtolower($l['status']), 'completed') !== false || strpos(strtolower($l['status']), 'warden approved') !== false)): ?>
                                                <button type="button" class="btn btn-success btn-sm" onclick="viewHostelOutpass(<?= $l['id'] ?>)">
                                                    🎫 QR Outpass
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal: Apply for Leave -->
    <div class="modal-overlay" id="applyLeaveModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Submit Leave Application</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form id="applyLeaveForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Leave Category *</label>
                        <select name="leave_type" class="form-control form-select" required>
                            <option value="Casual / Home Visit">Casual / Home Visit</option>
                            <option value="Medical Leave">Medical Leave</option>
                            <option value="On-Duty (OD)">On-Duty (OD / Technical Symposium)</option>
                            <option value="Emergency Leave">Emergency Leave</option>
                            <option value="Hostel Outpass">Hostel Weekend Outpass</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">From Date *</label>
                            <input type="date" name="from_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">To Date *</label>
                            <input type="date" name="to_date" class="form-control" value="<?= date('Y-m-d', strtotime('+3 day')) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Departure Time</label>
                            <input type="time" name="from_time" class="form-control" value="08:00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Expected Return Time</label>
                            <input type="time" name="to_time" class="form-control" value="18:00">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Detailed Reason for Leave *</label>
                        <textarea name="reason" rows="3" class="form-control" placeholder="Provide full justification for leave..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Destination Address during Leave *</label>
                        <input type="text" name="destination_address" class="form-control" placeholder="Full residential address during leave" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Emergency Phone Number</label>
                        <input type="tel" name="emergency_contact" class="form-control" value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit to Parent for Approval →</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: View Leave Letter & Timeline -->
    <div class="modal-overlay" id="leaveLetterModal">
        <div class="modal-box" style="max-width: 780px;">
            <div class="modal-header">
                <h3>Official Leave Application</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body" id="letterheadContent">
                <!-- Dynamically injected by dashboard.js -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
            </div>
        </div>
    </div>

    <!-- Modal: Digital Hostel Outpass with QR Code -->
    <div class="modal-overlay" id="hostelOutpassModal">
        <div class="modal-box" style="max-width: 720px;">
            <div class="modal-header">
                <h3>🎫 Hostel Digital Outpass & Gate Pass</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body" id="outpassModalContent">
                <!-- Dynamically generated with live QR Code -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    🖨️ Print / Download Gate Pass
                </button>
                <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/qrcode.js"></script>
    <script src="../assets/js/tts.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        document.getElementById('applyLeaveForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Submitting...';

            try {
                const formData = new FormData(e.target);
                const res = await fetch('../backend/leave_request.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    alert(data.message);
                    closeModal('applyLeaveModal');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Submit to Parent for Approval →';
                }
            } catch (err) {
                console.error(err);
                alert('Connection error occurred.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit to Parent for Approval →';
            }
        });
    </script>
</body>
</html>
