<?php
/**
 * CampusFlow - Head of Department (HOD) Dashboard
 * Features: Departmental Authorization & Automated Hostel / Day Scholar Branch Dispatch
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth_middleware.php';

$auth_data = require_auth('hod');
$user      = $auth_data['user'];
$hod       = $auth_data['profile'];

$dept = $hod['department'] ?? '';

// Fetch departmental leave requests forwarded by Class Advisors
$stmt = $pdo->prepare("
    SELECT lr.*, s.register_number, s.full_name AS student_name, s.department, s.year, s.section, s.hostel_status, s.phone AS student_phone,
           p.full_name AS parent_name, p.phone AS parent_phone
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.id
    LEFT JOIN parents p ON s.parent_id = p.id
    WHERE s.department = ? AND (
        lr.status = 'Advisor Approved' OR 
        lr.status = 'Waiting for HOD' OR 
        lr.status = 'Waiting for Warden' OR 
        lr.status = 'HOD Rejected' OR 
        lr.status = 'Warden Approved' OR 
        lr.status = 'Completed'
    )
    ORDER BY lr.created_at DESC
");
$stmt->execute([$dept]);
$leaves = $stmt->fetchAll();

// Statistics
$total_dept = count($leaves);
$pending_hod = 0;
$completed_leaves = 0;

foreach ($leaves as $l) {
    if ($l['status'] === 'Advisor Approved' || $l['status'] === 'Waiting for HOD') {
        $pending_hod++;
    } elseif ($l['status'] === 'Completed') {
        $completed_leaves++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Portal – CampusFlow</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="dashboard-layout">

    <!-- Top Navigation -->
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
                    <div class="user-avatar" style="background: linear-gradient(135deg, #1e3a8a, #3b82f6);"><?= strtoupper(substr($hod['full_name'] ?? 'H', 0, 1)) ?></div>
                    <div class="user-text">
                        <div class="user-name"><?= htmlspecialchars($hod['full_name'] ?? 'HOD') ?></div>
                        <div class="user-role-tag">Head of Department</div>
                    </div>
                </div>
                <a href="../backend/logout.php" class="btn btn-secondary btn-sm" style="background:#1e293b; color:#fff; border-color:#334155;">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="dashboard-content container">

        <!-- HOD Profile Banner -->
        <div class="profile-banner-card" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
            <div class="profile-banner-grid">
                <div class="profile-main-details">
                    <h1>Department Head Authorization Portal</h1>
                    <p style="color: #cbd5e1; font-size: 0.95rem;">
                        <?= htmlspecialchars($dept) ?>
                    </p>
                    <div class="profile-meta-tags">
                        <span class="meta-pill">🏛️ Head of Department: <strong><?= htmlspecialchars($hod['full_name']) ?></strong></span>
                        <span class="meta-pill">🆔 Faculty ID: <?= htmlspecialchars($hod['faculty_id_no']) ?></span>
                        <span class="meta-pill">📧 <?= htmlspecialchars($hod['email']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <span>Department Requests</span>
                    <h3><?= $total_dept ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-blue">🏛️</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Pending HOD Decision</span>
                    <h3><?= $pending_hod ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-yellow">⏳</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Fully Completed Leaves</span>
                    <h3><?= $completed_leaves ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-green">✅</div>
            </div>
        </div>

        <!-- Department Leaves Table -->
        <div class="section-toolbar">
            <h2>Department Leave Authorizations</h2>
        </div>

        <div class="table-card">
            <?php if (empty($leaves)): ?>
                <div class="empty-state">
                    <h4>No leave applications pending departmental authorization</h4>
                    <p>When Class Advisors forward approved leave requests, they will appear here.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Student Details</th>
                                <th>Hostel Status</th>
                                <th>Category & Dates</th>
                                <th>Reason</th>
                                <th>Advisor Approval</th>
                                <th>Current Status</th>
                                <th style="text-align: center; width: 280px;">HOD Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaves as $l): ?>
                                <?php 
                                    $st = $l['status'];
                                    $is_ready_for_hod = ($st === 'Advisor Approved' || $st === 'Waiting for HOD');
                                    $is_hosteller = strtolower($l['hostel_status']) === 'hosteller';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($l['student_name']) ?></strong><br>
                                        <span style="font-size:0.8rem; color:#64748b;"><?= htmlspecialchars($l['register_number']) ?> (Year <?= htmlspecialchars($l['year']) ?>-<?= htmlspecialchars($l['section']) ?>)</span>
                                    </td>
                                    <td>
                                        <span class="meta-pill <?= $is_hosteller ? 'hosteller' : 'day-scholar' ?>" style="font-size: 0.78rem;">
                                            <?= $is_hosteller ? '🏢 Hosteller' : '🚌 Day Scholar' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600; color: #1e40af;"><?= htmlspecialchars($l['leave_type']) ?></span><br>
                                        <span style="font-size: 0.8rem; color: #64748b;"><?= date('d M', strtotime($l['from_date'])) ?> - <?= date('d M Y', strtotime($l['to_date'])) ?></span>
                                    </td>
                                    <td style="max-width: 200px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                        <?= htmlspecialchars($l['reason']) ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-green">✓ Advisor Approved</span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= 
                                            (strpos(strtolower($st), 'completed') !== false || strpos(strtolower($st), 'approved') !== false) ? 'status-green' : 
                                            ((strpos(strtolower($st), 'rejected') !== false) ? 'status-red' : 'status-yellow') 
                                        ?>">
                                            <?= htmlspecialchars($st) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; gap: 0.35rem; flex-wrap: wrap; justify-content: center;">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="viewLeaveLetter(<?= $l['id'] ?>)">
                                                📄 View
                                            </button>

                                            <?php if ($is_ready_for_hod): ?>
                                                <button type="button" class="btn btn-success btn-sm" onclick="processFacultyAction('hod_approval.php', <?= $l['id'] ?>, 'approve', 'Approved by HOD')">
                                                    <?= $is_hosteller ? '✓ Approve (→ Warden)' : '✓ Final Approve (Completed)' ?>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="rejectHODAction(<?= $l['id'] ?>)">
                                                    ✗ Reject
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

    <!-- Modal: View Leave Letter & Timeline -->
    <div class="modal-overlay" id="leaveLetterModal">
        <div class="modal-box" style="max-width: 780px;">
            <div class="modal-header">
                <h3>Official Leave Application</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body" id="letterheadContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function rejectHODAction(leaveId) {
            const remarks = prompt('Enter reason for rejecting leave application:');
            if (remarks === null) return;
            processFacultyAction('hod_approval.php', leaveId, 'reject', remarks || 'HOD rejected');
        }
    </script>
</body>
</html>
