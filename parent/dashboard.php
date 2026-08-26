<?php
/**
 * CampusFlow - Parent Dashboard
 * Features: Child Leave Overview, Official Letter View, Multilingual TTS, and Voice Biometric Approval
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/auth_middleware.php';

$auth_data = require_auth('parent');
$user      = $auth_data['user'];
$parent    = $auth_data['profile'];

// Fetch all leave requests belonging to this parent's child
$stmt = $pdo->prepare("
    SELECT lr.*, s.register_number, s.full_name AS student_name, s.department, s.year, s.section, s.hostel_status, s.phone AS student_phone
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.id
    WHERE s.parent_id = ? OR s.register_number = ?
    ORDER BY lr.created_at DESC
");
$stmt->execute([(int)$parent['id'], $parent['student_reg_no']]);
$leaves = $stmt->fetchAll();

// Language display map
$lang_names = [
    'ta' => 'Tamil (தமிழ்)',
    'hi' => 'Hindi (हिन्दी)',
    'te' => 'Telugu (తెలుగు)',
    'ml' => 'Malayalam (മലയാളം)',
    'kn' => 'Kannada (ಕನ್ನಡ)',
    'en' => 'English'
];
$pref_lang_label = $lang_names[$parent['preferred_language'] ?? 'ta'] ?? 'Tamil (தமிழ்)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Portal – CampusFlow</title>
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
                    <div class="user-avatar" style="background: linear-gradient(135deg, #7c3aed, #a855f7);"><?= strtoupper(substr($parent['full_name'] ?? 'P', 0, 1)) ?></div>
                    <div class="user-text">
                        <div class="user-name"><?= htmlspecialchars($parent['full_name'] ?? 'Parent') ?></div>
                        <div class="user-role-tag">Parent / Guardian</div>
                    </div>
                </div>
                <a href="../backend/logout.php" class="btn btn-secondary btn-sm" style="background:#1e293b; color:#fff; border-color:#334155;">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="dashboard-content container">

        <!-- Parent & Ward Summary Banner -->
        <div class="profile-banner-card" style="background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 100%);">
            <div class="profile-banner-grid">
                <div class="profile-main-details">
                    <h1>Parent Authorization Desk</h1>
                    <p style="color: #e9d5ff; font-size: 0.95rem;">
                        Logged in as <strong><?= htmlspecialchars($parent['full_name']) ?></strong> (<?= htmlspecialchars($parent['phone']) ?>)
                    </p>
                    <div class="profile-meta-tags">
                        <span class="meta-pill">🎓 Ward: <strong><?= htmlspecialchars($parent['student_name'] ?? $parent['student_reg_no']) ?></strong></span>
                        <span class="meta-pill">🆔 Student ID: <?= htmlspecialchars($parent['student_reg_no']) ?></span>
                        <span class="meta-pill">🌐 Voice Reading Language: <strong><?= htmlspecialchars($pref_lang_label) ?></strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Child Leave Requests -->
        <div class="section-toolbar">
            <h2>Ward's Leave Applications</h2>
        </div>

        <div class="table-card">
            <?php if (empty($leaves)): ?>
                <div class="empty-state">
                    <h4>No leave applications received yet</h4>
                    <p>When your ward submits a leave request, it will appear here for voice review and authorization.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Category</th>
                                <th>Leave Dates</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th style="text-align: center; width: 340px;">Parent Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaves as $l): ?>
                                <?php 
                                    $is_pending_parent = ($l['status'] === 'Waiting for Parent' || $l['status'] === 'Submitted');
                                ?>
                                <tr class="<?= $is_pending_parent ? 'row-parent-pending' : (strpos(strtolower($l['status']), 'parent approved') !== false ? 'row-parent-approved' : '') ?>">
                                    <td><strong>#LR-<?= $l['id'] ?></strong></td>
                                    <td><span style="font-weight:600; color:#1e40af;"><?= htmlspecialchars($l['leave_type']) ?></span></td>
                                    <td>
                                        <?= date('d M Y', strtotime($l['from_date'])) ?> to <?= date('d M Y', strtotime($l['to_date'])) ?>
                                    </td>
                                    <td style="max-width: 200px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">
                                        <?= htmlspecialchars($l['reason']) ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= 
                                            (strpos(strtolower($l['status']), 'approved') !== false || strpos(strtolower($l['status']), 'completed') !== false) ? 'status-green' : 
                                            ((strpos(strtolower($l['status']), 'rejected') !== false) ? 'status-red' : 'status-yellow') 
                                        ?>">
                                            <?= htmlspecialchars($l['status']) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div style="display: inline-flex; gap: 0.4rem; flex-wrap: wrap; justify-content: center;">
                                            <!-- 1. Open and read complete letter -->
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="viewLeaveLetter(<?= $l['id'] ?>)">
                                                📄 Read Letter
                                            </button>

                                            <!-- 2. Voice Reading Button (Listen to Letter) -->
                                            <button type="button" class="btn btn-listen btn-sm" onclick="listenToLetter(<?= $l['id'] ?>, '<?= $parent['preferred_language'] ?? 'ta' ?>')">
                                                🔊 Listen
                                            </button>

                                            <!-- 3. Voice Approval Button (Only if pending parent) -->
                                            <?php if ($is_pending_parent): ?>
                                                <button type="button" class="btn btn-voice btn-sm" onclick="openParentVoiceApprovalModal(<?= $l['id'] ?>, '<?= addslashes($l['student_name'] ?? 'Student') ?>', '<?= addslashes($l['register_number']) ?>', '<?= $parent['preferred_language'] ?? 'ta' ?>')">
                                                    🎙️ Voice Approval
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

    <!-- Modal: View Leave Letter (with TTS Listen control) -->
    <div class="modal-overlay" id="leaveLetterModal">
        <div class="modal-box" style="max-width: 780px;">
            <div class="modal-header">
                <h3>Official Leave Letter</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body" id="letterheadContent">
                <!-- Dynamically populated -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-listen" id="btnListenLetterModal">
                    🔊 Listen to Letter
                </button>
                <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
            </div>
        </div>
    </div>

    <!-- Modal: Parent Voice Approval & Biometric Verification -->
    <div class="modal-overlay" id="parentVoiceModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Parent Voice Approval</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.25rem;">
                    <div style="font-size: 0.9rem; color: #1e3a8a;">
                        Authorizing Leave for: <strong id="voiceTargetStudent">Student Name</strong>
                    </div>
                    <div style="font-size: 0.82rem; color: #64748b; margin-top: 0.25rem;">
                        Preferred Language: <strong><?= htmlspecialchars($pref_lang_label) ?></strong>
                    </div>
                </div>

                <div style="text-align: center; margin-bottom: 1.25rem;">
                    <p style="font-size: 0.9rem; color: #334155; margin-bottom: 0.5rem;">
                        <strong>Instructions:</strong> Press the button below and speak your consent clearly (e.g. <em>"I approve the leave"</em> or <em>"விடுப்புக்கு ஒப்புதல் அளிக்கிறேன்"</em>).
                    </p>

                    <!-- Real-time Live Audio Visualizer -->
                    <div class="audio-visualizer" id="voiceApprovalVisualizer">
                        <div class="wave-bar"></div><div class="wave-bar"></div>
                        <div class="wave-bar"></div><div class="wave-bar"></div>
                        <div class="wave-bar"></div><div class="wave-bar"></div>
                        <div class="wave-bar"></div><div class="wave-bar"></div>
                    </div>

                    <button type="button" class="btn btn-voice btn-lg" id="btnStartVoiceApproval">
                        🔴 Hold & Record Voice Approval
                    </button>

                    <div id="liveTranscript" style="font-style: italic; color: #475569; margin-top: 0.75rem; font-size: 0.9rem;">
                        "Press record to speak your consent..."
                    </div>
                </div>

                <!-- Hidden inputs -->
                <input type="hidden" id="voiceApprovalLeaveId">
                <input type="hidden" id="recordedAudioInput">
                <input type="hidden" id="spokenTranscriptInput">
                <input type="hidden" id="matchScoreInput" value="0">

                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label">Parent Remarks / Notes (Optional)</label>
                    <input type="text" id="parentRemarksInput" class="form-control" placeholder="e.g. Approved for family event">
                </div>

                <!-- Score Match Progress Bar & Verification Box -->
                <div id="voiceVerifyResult" class="alert" style="display: none; margin-top: 1rem;"></div>
                <div class="score-meter-box" id="scoreMeterContainer" style="display: none;">
                    <div id="voiceScoreText" style="font-size: 0.85rem; color: #334155;">Voice Biometric Match Score: <strong>0%</strong></div>
                    <div class="score-bar-track">
                        <div class="score-bar-fill" id="voiceScoreBar"></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger btn-sm" onclick="rejectByParent()">
                    Decline Request
                </button>
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="button" class="btn btn-success" id="btnSubmitVoiceApproval" onclick="submitParentApproval('approve')" disabled>
                    Confirm Voice Approval ✓
                </button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/tts.js"></script>
    <script src="../assets/js/voice_verify.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        async function listenToLetter(leaveId, lang) {
            try {
                const res = await fetch(`../backend/leave_request.php?action=get_leave_details&id=${leaveId}`);
                const data = await res.json();
                if (data.success) {
                    window.CampusTTS.speakLetter(data.leave, lang);
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function rejectByParent() {
            const leaveId = document.getElementById('voiceApprovalLeaveId').value;
            const remarks = prompt('Please enter reason for declining this leave request:', 'Parent declined');
            if (remarks === null) return;

            document.getElementById('parentRemarksInput').value = remarks;
            submitParentApproval('reject');
        }
    </script>
</body>
</html>
