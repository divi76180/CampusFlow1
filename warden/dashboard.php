<?php
/**
 * CampusFlow - Hostel Warden Dashboard
 * Features: Hosteller Leave Authorizations & Final Gate Pass Issuance
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth_middleware.php';

$auth_data = require_auth('warden');
$user      = $auth_data['user'];
$warden    = $auth_data['profile'];

// Fetch Hosteller leave requests approved by HOD
$stmt = $pdo->query("
    SELECT lr.*, s.register_number, s.full_name AS student_name, s.department, s.year, s.section, s.hostel_status, s.phone AS student_phone,
           p.full_name AS parent_name, p.phone AS parent_phone
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.id
    LEFT JOIN parents p ON s.parent_id = p.id
    WHERE s.hostel_status = 'hosteller' AND (
        lr.status = 'Waiting for Warden' OR 
        lr.status = 'HOD Approved' OR 
        lr.status = 'Warden Approved' OR 
        lr.status = 'Warden Rejected' OR 
        lr.status = 'Completed'
    )
    ORDER BY lr.created_at DESC
");
$leaves = $stmt->fetchAll();

// Statistics
$total_hostel = count($leaves);
$pending_warden = 0;
$gatepasses_issued = 0;

foreach ($leaves as $l) {
    if ($l['status'] === 'Waiting for Warden' || $l['status'] === 'HOD Approved') {
        $pending_warden++;
    } elseif ($l['status'] === 'Completed' || $l['status'] === 'Warden Approved') {
        $gatepasses_issued++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hostel Warden Portal – CampusFlow</title>
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
                    <div class="user-avatar" style="background: linear-gradient(135deg, #d97706, #f59e0b);"><?= strtoupper(substr($warden['full_name'] ?? 'W', 0, 1)) ?></div>
                    <div class="user-text">
                        <div class="user-name"><?= htmlspecialchars($warden['full_name'] ?? 'Warden') ?></div>
                        <div class="user-role-tag">Hostel Warden</div>
                    </div>
                </div>
                <a href="../backend/logout.php" class="btn btn-secondary btn-sm" style="background:#1e293b; color:#fff; border-color:#334155;">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="dashboard-content container">

        <!-- Warden Profile Banner -->
        <div class="profile-banner-card" style="background: linear-gradient(135deg, #78350f 0%, #b45309 100%);">
            <div class="profile-banner-grid">
                <div class="profile-main-details">
                    <h1>Hostel Outpass & Gate Clearance Desk</h1>
                    <p style="color: #fef3c7; font-size: 0.95rem;">
                        <?= htmlspecialchars($warden['hostel_name'] ?? 'Institutional Hostels') ?>
                    </p>
                    <div class="profile-meta-tags">
                        <span class="meta-pill">🏢 Hostel: <strong><?= htmlspecialchars($warden['hostel_name'] ?? 'Hostel Warden') ?></strong></span>
                        <span class="meta-pill">🆔 Staff ID: <?= htmlspecialchars($warden['faculty_id_no']) ?></span>
                        <span class="meta-pill">📧 <?= htmlspecialchars($warden['email']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <span>Hosteller Outpass Requests</span>
                    <h3><?= $total_hostel ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-blue">🏢</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Pending Warden Clearance</span>
                    <h3><?= $pending_warden ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-yellow">⏳</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Final Gate Passes Issued</span>
                    <h3><?= $gatepasses_issued ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-green">🎫</div>
            </div>
        </div>

        <!-- Hosteller Leaves Table -->
        <div class="section-toolbar">
            <div>
                <h2>Pre-Authorized Hosteller Requests</h2>
                <p style="color:#64748b; font-size:0.85rem; margin-top:0.2rem;">
                    Requests shown below have already received Parent, Class Advisor, and HOD approvals.
                </p>
            </div>
        </div>

        <div class="table-card">
            <?php if (empty($leaves)): ?>
                <div class="empty-state">
                    <h4>No Hosteller requests awaiting Warden clearance</h4>
                    <p>When HOD approves Hosteller leave requests, they will appear here for final gate clearance.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Hosteller Details</th>
                                <th>Department</th>
                                <th>Leave Dates</th>
                                <th>Destination</th>
                                <th>Prior Approvals</th>
                                <th>Status</th>
                                <th style="text-align: center; width: 280px;">Warden Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaves as $l): ?>
                                <?php 
                                    $st = $l['status'];
                                    $is_ready_for_warden = ($st === 'Waiting for Warden' || $st === 'HOD Approved');
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($l['student_name']) ?></strong><br>
                                        <span style="font-size:0.8rem; color:#64748b;"><?= htmlspecialchars($l['register_number']) ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($l['department']) ?><br>
                                        <span style="font-size:0.8rem; color:#64748b;">Year <?= htmlspecialchars($l['year']) ?>-<?= htmlspecialchars($l['section']) ?></span>
                                    </td>
                                    <td>
                                        <span style="font-weight:600; color:#1e40af;"><?= htmlspecialchars($l['leave_type']) ?></span><br>
                                        <span style="font-size:0.8rem; color:#64748b;"><?= date('d M', strtotime($l['from_date'])) ?> - <?= date('d M Y', strtotime($l['to_date'])) ?></span>
                                    </td>
                                    <td style="max-width: 180px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                        <?= htmlspecialchars($l['destination_address']) ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-green" style="font-size: 0.75rem;">✓ Parent + Advisor + HOD</span>
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

                                            <?php if ($is_ready_for_warden): ?>
                                                <button type="button" class="btn btn-success btn-sm" onclick="processFacultyAction('warden_approval.php', <?= $l['id'] ?>, 'approve', 'Hostel Outpass Authorized by Warden')">
                                                    ✓ Issue Outpass (Complete)
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="rejectWardenAction(<?= $l['id'] ?>)">
                                                    ✗ Reject
                                                </button>
                                            <?php elseif (strpos(strtolower($st), 'completed') !== false): ?>
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

    <!-- Modal: View Leave Letter & Timeline -->
    <div class="modal-overlay" id="leaveLetterModal">
        <div class="modal-box" style="max-width: 780px;">
            <div class="modal-header">
                <h3>Hosteller Leave & Outpass Authorization</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body" id="letterheadContent"></div>
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
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function rejectWardenAction(leaveId) {
            const remarks = prompt('Enter reason for rejecting hostel outpass:');
            if (remarks === null) return;
            processFacultyAction('warden_approval.php', leaveId, 'reject', remarks || 'Warden rejected');
        }
    </script>
</body>
</html>
