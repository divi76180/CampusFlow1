<?php
/**
 * CampusFlow - Class Advisor Dashboard
 * Features: Student leaves filtered by Dept & Section, Parent Status Highlights, and HOD Forwarding
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth_middleware.php';

$auth_data = require_auth('advisor');
$user      = $auth_data['user'];
$advisor   = $auth_data['profile'];

// Fetch all leave requests for this advisor's department & section
$dept = $advisor['department'] ?? '';
$sec  = $advisor['section_handled'] ?? '';

// Build query
$query = "
    SELECT lr.*, s.register_number, s.full_name AS student_name, s.department, s.year, s.section, s.hostel_status, s.phone AS student_phone,
           p.full_name AS parent_name, p.phone AS parent_phone,
           vv.match_score, vv.is_verified AS voice_verified
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.id
    LEFT JOIN parents p ON s.parent_id = p.id
    LEFT JOIN voice_verifications vv ON vv.leave_id = lr.id
    WHERE s.department = ?
";
$params = [$dept];

if (!empty($sec)) {
    // If section format is like '3-A' or 'A', match accordingly
    $clean_sec = preg_replace('/[^A-Za-z0-9]/', '', $sec);
    $query .= " AND (s.section LIKE ? OR ? LIKE CONCAT('%', s.section, '%'))";
    $params[] = '%' . substr($clean_sec, -1) . '%';
    $params[] = $sec;
}

$query .= " ORDER BY lr.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$leaves = $stmt->fetchAll();

// Statistics
$total_handled = count($leaves);
$pending_advisor = 0;
$approved_by_parent = 0;

foreach ($leaves as $l) {
    if ($l['status'] === 'Parent Approved' || $l['status'] === 'Waiting for Class Advisor') {
        $pending_advisor++;
        $approved_by_parent++;
    } elseif (strpos(strtolower($l['status']), 'advisor approved') !== false || strpos(strtolower($l['status']), 'hod') !== false || strpos(strtolower($l['status']), 'completed') !== false) {
        $approved_by_parent++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Advisor Portal – CampusFlow</title>
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
                    <div class="user-avatar" style="background: linear-gradient(135deg, #0284c7, #38bdf8);"><?= strtoupper(substr($advisor['full_name'] ?? 'A', 0, 1)) ?></div>
                    <div class="user-text">
                        <div class="user-name"><?= htmlspecialchars($advisor['full_name'] ?? 'Advisor') ?></div>
                        <div class="user-role-tag">Class Advisor (<?= htmlspecialchars($advisor['faculty_id_no'] ?? '') ?>)</div>
                    </div>
                </div>
                <a href="../backend/logout.php" class="btn btn-secondary btn-sm" style="background:#1e293b; color:#fff; border-color:#334155;">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="dashboard-content container">

        <!-- Advisor Profile Banner -->
        <div class="profile-banner-card" style="background: linear-gradient(135deg, #0369a1 0%, #0284c7 100%);">
            <div class="profile-banner-grid">
                <div class="profile-main-details">
                    <h1>Class Advisor Approval Desk</h1>
                    <p style="color: #e0f2fe; font-size: 0.95rem;">
                        <?= htmlspecialchars($advisor['department']) ?>
                    </p>
                    <div class="profile-meta-tags">
                        <span class="meta-pill">📋 Class Handled: <strong><?= htmlspecialchars($advisor['section_handled'] ?? 'Assigned Section') ?></strong></span>
                        <span class="meta-pill">🆔 Faculty ID: <?= htmlspecialchars($advisor['faculty_id_no']) ?></span>
                        <span class="meta-pill">📧 <?= htmlspecialchars($advisor['email']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <span>Total Class Requests</span>
                    <h3><?= $total_handled ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-blue">📊</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Pending Advisor Action</span>
                    <h3><?= $pending_advisor ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-yellow">⏳</div>
            </div>
            <div class="stat-card">
                <div class="stat-info">
                    <span>Parent-Verified Requests</span>
                    <h3><?= $approved_by_parent ?></h3>
                </div>
                <div class="stat-icon-wrapper stat-icon-green">🎙️</div>
            </div>
        </div>

        <!-- Table Header & Status Legend -->
        <div class="section-toolbar">
            <div>
                <h2>Student Leave Submissions</h2>
                <p style="color:#64748b; font-size:0.85rem; margin-top:0.2rem;">
                    Status highlights: <span class="status-badge status-green" style="font-size:0.75rem;">Parent Approved</span> <span class="status-badge status-yellow" style="font-size:0.75rem;">Parent Pending</span> <span class="status-badge status-red" style="font-size:0.75rem;">Parent Rejected</span>
                </p>
            </div>
        </div>

        <div class="table-card">
            <?php if (empty($leaves)): ?>
                <div class="empty-state">
                    <h4>No student leave requests found for your class</h4>
                    <p>Applications submitted by your assigned students will appear here automatically.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Student Details</th>
                                <th>Type & Dates</th>
                                <th>Reason</th>
                                <th>Parent Status</th>
                                <th>Current Stage</th>
                                <th style="text-align: center; width: 260px;">Advisor Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaves as $l): ?>
                                <?php 
                                    $st = $l['status'];
                                    $parent_approved = (strpos(strtolower($st), 'parent approved') !== false || strpos(strtolower($st), 'waiting for class advisor') !== false || strpos(strtolower($st), 'advisor approved') !== false || strpos(strtolower($st), 'hod') !== false || strpos(strtolower($st), 'warden') !== false || strpos(strtolower($st), 'completed') !== false);
                                    $parent_rejected = (strpos(strtolower($st), 'parent rejected') !== false);
                                    $is_ready_for_advisor = ($st === 'Parent Approved' || $st === 'Waiting for Class Advisor');

                                    // Color highlight row class matching Idea.txt
                                    $row_class = $parent_approved ? 'row-parent-approved' : ($parent_rejected ? 'row-parent-rejected' : 'row-parent-pending');
                                ?>
                                <tr class="<?= $row_class ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($l['student_name']) ?></strong><br>
                                        <span style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($l['register_number']) ?> (Sec <?= htmlspecialchars($l['section']) ?>)</span>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600; color: #1e40af;"><?= htmlspecialchars($l['leave_type']) ?></span><br>
                                        <span style="font-size: 0.8rem; color: #64748b;"><?= date('d M', strtotime($l['from_date'])) ?> - <?= date('d M Y', strtotime($l['to_date'])) ?></span>
                                    </td>
                                    <td style="max-width: 220px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                        <?= htmlspecialchars($l['reason']) ?>
                                    </td>
                                    <td>
                                        <?php if ($parent_approved): ?>
                                            <span class="status-badge status-green">
                                                ✓ Parent Approved <?= !empty($l['match_score']) ? "({$l['match_score']}%)" : '' ?>
                                            </span>
                                        <?php elseif ($parent_rejected): ?>
                                            <span class="status-badge status-red">✗ Parent Rejected</span>
                                        <?php else: ?>
                                            <span class="status-badge status-yellow">⏳ Parent Pending</span>
                                        <?php endif; ?>
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

                                            <?php if ($is_ready_for_advisor): ?>
                                                <button type="button" class="btn btn-success btn-sm" onclick="processFacultyAction('advisor_approval.php', <?= $l['id'] ?>, 'approve', 'Approved by Class Advisor')">
                                                    ✓ Approve → HOD
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="rejectAdvisorAction(<?= $l['id'] ?>)">
                                                    ✗ Reject
                                                </button>
                                            <?php elseif (!$parent_approved): ?>
                                                <span style="font-size: 0.78rem; color: #d97706; font-weight: 600; padding: 0.35rem;">
                                                    Awaiting Parent
                                                </span>
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
        function rejectAdvisorAction(leaveId) {
            const remarks = prompt('Enter reason for rejecting leave request:');
            if (remarks === null) return;
            processFacultyAction('advisor_approval.php', leaveId, 'reject', remarks || 'Advisor rejected');
        }
    </script>
</body>
</html>
