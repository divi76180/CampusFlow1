function formatStudentDepartment(dept, regNo) {
    if (regNo) {
        const u = String(regNo).toUpperCase().trim();
        if (u.includes('ITR') || u.includes('IT')) return 'Information Technology (IT)';
        if (u.includes('CSR') || u.includes('CSE') || u.includes('CS')) return 'Computer Science and Engineering (CSE)';
        if (u.includes('ADR') || u.includes('AIDS') || u.includes('AD')) return 'Artificial Intelligence & Data Science (AI & DS)';
        if (u.includes('ECR') || u.includes('ECE') || u.includes('EC')) return 'Electronics & Communication (ECE)';
        if (u.includes('EER') || u.includes('EEE') || u.includes('EE')) return 'Electrical & Electronics (EEE)';
        if (u.includes('MER') || u.includes('MECH') || u.includes('ME')) return 'Mechanical Engineering (MECH)';
        if (u.includes('CIR') || u.includes('CIVIL') || u.includes('CE')) return 'Civil Engineering (CIVIL)';
        if (u.includes('MTR') || u.includes('MTS')) return 'Mechatronics Engineering (MTS)';
        if (u.includes('BTR') || u.includes('BT')) return 'Biotechnology (BT)';
        if (u.includes('CHR') || u.includes('CHEM')) return 'Chemical Engineering (CHEM)';
    }
    if (dept) {
        const d = String(dept).toLowerCase().trim();
        if (d.includes('information') || d === 'it') return 'Information Technology (IT)';
        if (d.includes('computer') || d.includes('cse') || d === 'cs') return 'Computer Science and Engineering (CSE)';
        if (d.includes('artificial') || d.includes('aids') || d.includes('ai & ds')) return 'Artificial Intelligence & Data Science (AI & DS)';
        if (d.includes('electronics') || d.includes('ece') || d === 'ec') return 'Electronics & Communication (ECE)';
        if (d.includes('electrical') || d.includes('eee') || d === 'ee') return 'Electrical & Electronics (EEE)';
        if (d.includes('mechanical') || d.includes('mech') || d === 'me') return 'Mechanical Engineering (MECH)';
        if (d.includes('civil') || d === 'ce') return 'Civil Engineering (CIVIL)';
        if (d.includes('mechatronics') || d.includes('mts')) return 'Mechatronics Engineering (MTS)';
        if (d.includes('biotechnology') || d.includes('bt')) return 'Biotechnology (BT)';
        if (d.includes('chemical') || d.includes('chem')) return 'Chemical Engineering (CHEM)';
        return dept;
    }
    return 'Engineering';
}
window.formatStudentDepartment = formatStudentDepartment;

/**
 * Open a specific modal by ID
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}
window.openModal = openModal;

/**
 * Close a specific modal by ID
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        if (window.CampusTTS) window.CampusTTS.stop();
        if (window.CampusVoice && window.CampusVoice.isRecording) {
            window.CampusVoice.stopRecording();
        }
    }
}
window.closeModal = closeModal;

document.addEventListener('DOMContentLoaded', () => {
    // Close modal on click outside or close button
    document.querySelectorAll('.modal-close, [data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const overlay = btn.closest('.modal-overlay');
            if (overlay) {
                overlay.classList.remove('active');
                if (window.CampusTTS) window.CampusTTS.stop();
                if (window.CampusVoice && window.CampusVoice.isRecording) {
                    window.CampusVoice.stopRecording();
                }
            }
        });
    });

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
                if (window.CampusTTS) window.CampusTTS.stop();
                if (window.CampusVoice && window.CampusVoice.isRecording) {
                    window.CampusVoice.stopRecording();
                }
            }
        });
    });
});

/**
 * Render Official Leave Application Letterhead Modal
 * @param {Object} data - Can be { leave: {...}, approvals: [...] } or direct leave object
 */
function renderLeaveLetterModal(data) {
    if (!data) return;
    const leaveContainer = document.getElementById('letterheadContent');
    if (!leaveContainer) return;

    const leave = data.leave || data || {};
    const approvals = data.approvals || leave.approvals || [];

    const fromDateFormatted = leave.from_date ? new Date(leave.from_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A';
    const toDateFormatted = leave.to_date ? new Date(leave.to_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A';
    const fromTimeStr = leave.from_time ? leave.from_time.substring(0, 5) : '08:00';
    const toTimeStr = leave.to_time ? leave.to_time.substring(0, 5) : '18:00';
    const createdFormatted = new Date(leave.created_at || Date.now()).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
    
    const stdName = leave.student_name || leave.students?.full_name || 'Student';
    const regNo = leave.register_number || leave.students?.register_number || 'N/A';
    const dept = formatStudentDepartment(leave.department || leave.students?.department, regNo);
    const yr = leave.year || leave.students?.year || 3;
    const sec = leave.section || leave.students?.section || 'A';
    const hostelStatus = (leave.hostel_status || leave.students?.hostel_status || 'day_scholar').toLowerCase();
    const isHosteller = hostelStatus === 'hosteller';
    const hostelBlock = leave.hostel_block || leave.students?.hostel_block || 'Hostel Block';
    const roomNo = leave.room_number || leave.students?.room_number || '';
    const statusText = leave.status || 'Waiting for Parent';
    const parentName = leave.parent_name || 'Parent / Guardian';
    const parentPhone = leave.parent_phone || leave.emergency_contact || 'Registered Mobile';

    const parentApprovedInTrail = approvals && approvals.some(a => a.approver_role === 'parent' && a.action === 'approved');
    const isParentApproved = statusText.includes('Parent Approved') || statusText.includes('Advisor Approved') || statusText.includes('Waiting for Warden') || statusText.toLowerCase().includes('completed') || parentApprovedInTrail;

    leaveContainer.innerHTML = `
        <div class="letterhead-doc">
            <div class="letterhead-header">
                <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.08em; color:#64748b; font-weight:700; margin-bottom:0.25rem;">
                    CampusFlow &bull; Digital Leave &amp; Outpass Management System
                </div>
                <h2>CampusFlow College of Engineering &amp; Technology</h2>
                <p style="margin-top:0.25rem; font-weight:600; color:#1e40af;">
                    Department of ${escapeHtml(dept)} &bull; Official Leave Application
                </p>
                <div style="margin-top:0.4rem; font-size:0.75rem; color:#64748b; font-family:monospace; font-weight:700;">
                    Application Ref: #LR-2026-00${leave.id || '1'} &bull; Date: ${createdFormatted}
                </div>
            </div>

            <div class="letterhead-meta">
                <div>
                    <strong>Student Name:</strong> ${escapeHtml(stdName)}<br>
                    <strong>Register Number:</strong> <span style="font-family:monospace; font-weight:700; color:#1e40af;">${escapeHtml(regNo)}</span><br>
                    <strong>Department &amp; Class:</strong> ${escapeHtml(dept)} &bull; Year ${yr}, Sec ${sec}<br>
                    <strong>Hostel Status:</strong> <span class="meta-pill ${isHosteller ? 'hosteller' : 'day-scholar'}" style="font-size:0.75rem; padding:2px 8px;">${isHosteller ? `🏢 Hosteller (${escapeHtml(hostelBlock)}${roomNo ? `, Room ${escapeHtml(roomNo)}` : ''})` : '🏠 Day Scholar'}</span>
                </div>
                <div>
                    <strong>Parent / Guardian:</strong> ${escapeHtml(parentName)}<br>
                    <strong>Contact Number:</strong> +91 ${escapeHtml(parentPhone)}<br>
                    <strong>Leave Category:</strong> <span style="color:#1e40af; font-weight:700;">${escapeHtml(leave.leave_type || 'Leave Request')}</span><br>
                    <strong>Current Status:</strong> <span class="status-badge ${getStatusClass(statusText)}">${escapeHtml(statusText)}</span>
                </div>
            </div>

            <div class="letterhead-body">
                <p style="margin-bottom:0.75rem;">
                    <strong>To:</strong><br>
                    &bull; <strong>Parent / Guardian:</strong> ${escapeHtml(parentName)} (+91 ${escapeHtml(parentPhone)})<br>
                    &bull; <strong>Class Advisor:</strong> Class Advisor (${escapeHtml(dept)} - Year ${yr}, Sec ${sec})<br>
                    &bull; <strong>Head of Department:</strong> Department of ${escapeHtml(dept)}<br>
                    ${isHosteller ? `&bull; <strong>Hostel Warden:</strong> ${escapeHtml(hostelBlock)}<br>` : ''}
                    <em>CampusFlow College of Engineering &amp; Technology</em>
                </p>

                <p style="margin-bottom:0.75rem; font-weight:700; color:#0f172a;">
                    Sub: Application for Leave of Absence (${escapeHtml(leave.leave_type || 'Leave')}) - Reg.
                </p>

                <p style="margin-bottom:0.75rem;"><strong>Respected Sir / Madam,</strong></p>
                <p style="margin-bottom:0.75rem;">
                    I, <strong>${escapeHtml(stdName)}</strong> (Register No: <strong>${escapeHtml(regNo)}</strong>), student of ${escapeHtml(dept)}, Year ${yr}, Section ${sec}, request your kind permission to grant me leave of absence from <strong>${fromDateFormatted} (${fromTimeStr})</strong> to <strong>${toDateFormatted} (${toTimeStr})</strong>.
                </p>

                <p style="margin-bottom:0.75rem;">
                    <strong>Reason for Leave:</strong><br>
                    <span style="background:#f8fafc; display:block; border-left:3px solid #2563eb; padding:0.5rem 0.75rem; margin-top:0.25rem; font-style:italic;">
                        "${escapeHtml(leave.reason || 'N/A')}"
                    </span>
                </p>

                <p style="margin-bottom:0.75rem;">
                    <strong>Destination Address during Leave:</strong><br>
                    <span style="color:#334155; font-weight:500;">${escapeHtml(leave.destination_address || 'N/A')}</span>
                </p>
            </div>

            <div class="letterhead-footer">
                <div>
                    <strong>Parent Authorization Verification:</strong><br>
                    ${isParentApproved 
                        ? `<div style="margin-top:0.35rem;">
                             <span class="status-badge status-green" style="display:inline-block; font-size:0.78rem;">✅ Verified via SMS OTP (Phone: +91 ${escapeHtml(parentPhone)})</span>
                             <div style="font-size:0.72rem; color:#64748b; margin-top:3px;">6-Digit Cryptographic OTP Security Confirmed</div>
                           </div>` 
                        : `<div style="margin-top:0.35rem;">
                             <span class="status-badge status-yellow" style="display:inline-block; font-size:0.78rem;">⏳ Pending Parent SMS OTP Authorization</span>
                             <div style="font-size:0.72rem; color:#94a3b8; margin-top:3px;">Parent verification required to advance to Class Advisor</div>
                           </div>`}
                </div>
                <div style="text-align: right;">
                    <strong>Student Digital Signature:</strong><br>
                    <span style="font-family:cursive, 'Brush Script MT', sans-serif; font-size:1.25rem; color:#1e3a8a; display:inline-block; margin-top:4px;">
                        ${escapeHtml(stdName)}
                    </span>
                    <div style="font-size:0.7rem; color:#94a3b8; font-family:monospace;">Reg: ${escapeHtml(regNo)} &bull; ${createdFormatted}</div>
                </div>
            </div>
        </div>

        <!-- Approval Progression History -->
        <div style="margin-top: 1.5rem;">
            <h4 style="font-size: 1rem; margin-bottom: 0.75rem; color: #0f172a; display:flex; align-items:center; gap:6px;">
                <span>📋</span> Approval Progression &amp; Audit Trail
            </h4>
            ${renderApprovalsAudit(approvals)}
        </div>
    `;
}
window.renderLeaveLetterModal = renderLeaveLetterModal;
window.renderLetterhead = renderLeaveLetterModal;

function renderHostelOutpassModal(data) {
    if (!data) return;
    const leave = data.leave || data || {};
    const container = document.getElementById('outpassModalContent');
    if (!container) return;

    const fromDateFormatted = leave.from_date ? new Date(leave.from_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A';
    const toDateFormatted = leave.to_date ? new Date(leave.to_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A';
    const fromTime = leave.from_time ? leave.from_time.substring(0, 5) : '08:00';
    const toTime = leave.to_time ? leave.to_time.substring(0, 5) : '18:00';
    const stdName = leave.student_name || leave.students?.full_name || 'Hosteller';
    const regNo = leave.register_number || leave.students?.register_number || 'N/A';
    const dept = formatStudentDepartment(leave.department || leave.students?.department, regNo);
    const yr = leave.year || leave.students?.year || 3;
    const sec = leave.section || leave.students?.section || 'A';
    const hostelName = leave.hostel_block || leave.students?.hostel_block || 'Hostel Block';
    const roomNo = leave.room_number || leave.students?.room_number || '';

    const securityUrl = `${window.location.origin}/security_gate.html?pass_id=${leave.id}&reg=${encodeURIComponent(regNo)}&status=VALID`;

    const isSickInHostel = (leave.leave_type || '').includes('Staying in Hostel') || (leave.destination_address || '').includes('In-Hostel') || (leave.leave_type || '').includes('Sick Rest');

    container.innerHTML = `
        <div class="outpass-container" id="printableOutpassCard">
            <div class="outpass-topbar" style="${isSickInHostel ? 'background: linear-gradient(135deg, #065f46 0%, #047857 100%);' : ''}">
                <div>
                    <div class="outpass-topbar-brand">${isSickInHostel ? '🏥 CampusFlow In-Hostel Medical Pass' : 'CampusFlow Digital Gate Pass'}</div>
                    <div style="font-size:0.75rem; color:#cbd5e1; letter-spacing:0.03em;">${isSickInHostel ? 'IN-HOSTEL ROOM REST &amp; ATTENDANCE EXEMPTION' : 'HOSTEL OUTPASS &amp; SECURITY CLEARANCE'}</div>
                </div>
                <div class="outpass-security-badge" style="${isSickInHostel ? 'background: rgba(255,255,255,0.2); color:#fff;' : ''}">
                    <span>${isSickInHostel ? '🏥' : '🛡️'}</span>
                    <span>${isSickInHostel ? 'ROOM REST PERMITTED' : 'OFFICIALLY VERIFIED'}</span>
                </div>
            </div>

            <div class="outpass-body-grid">
                <div class="outpass-student-details">
                    <div class="outpass-row">
                        <strong>Pass Reference ID:</strong>
                        <span style="font-family:monospace; font-weight:700; color:#1e40af;">#${isSickInHostel ? 'MED' : 'OP'}-2026-00${leave.id}</span>
                    </div>
                    <div class="outpass-row">
                        <strong>Hosteller Name:</strong>
                        <span><strong>${escapeHtml(stdName)}</strong></span>
                    </div>
                    <div class="outpass-row">
                        <strong>Register Number:</strong>
                        <span style="font-family:monospace; font-weight:700;">${escapeHtml(regNo)}</span>
                    </div>
                    <div class="outpass-row">
                        <strong>Department &amp; Class:</strong>
                        <span>${escapeHtml(dept)} (${yr}-${sec})</span>
                    </div>
                    <div class="outpass-row">
                        <strong>Hostel &amp; Room:</strong>
                        <span style="color:#d97706; font-weight:600;">🏠 ${escapeHtml(hostelName)}${roomNo ? ` (${escapeHtml(roomNo)})` : ''}</span>
                    </div>
                    <div class="outpass-row">
                        <strong>Category:</strong>
                        <span style="color:${isSickInHostel ? '#059669' : '#1e40af'}; font-weight:700;">${escapeHtml(leave.leave_type || 'Hostel Outpass')}</span>
                    </div>
                    <div class="outpass-row">
                        <strong>${isSickInHostel ? 'Rest Start Time:' : 'Valid Exit Time:'}</strong>
                        <span style="color:#059669; font-weight:700;">${fromDateFormatted} @ ${fromTime}</span>
                    </div>
                    <div class="outpass-row">
                        <strong>${isSickInHostel ? 'Resume Classes:' : 'Valid Entry / Return:'}</strong>
                        <span style="color:#dc2626; font-weight:700;">${toDateFormatted} @ ${toTime}</span>
                    </div>
                    <div class="outpass-row">
                        <strong>Stay Location:</strong>
                        <span>${escapeHtml(leave.destination_address || 'In-Hostel Room')}</span>
                    </div>
                    <div class="outpass-row">
                        <strong>Parent Contact:</strong>
                        <span>+91 ${escapeHtml(leave.parent_phone || leave.emergency_contact || 'N/A')}</span>
                    </div>
                </div>

                <div class="outpass-qr-frame">
                    <div id="outpassQrCanvasContainer" style="display:flex; justify-content:center; align-items:center; min-height:170px;"></div>
                    <div class="outpass-qr-caption">${isSickInHostel ? '🏠 In-Campus Stay (No Exit)' : '📷 Scan at Main Gate'}</div>
                    <div style="font-size:0.7rem; color:#64748b; margin-top:0.25rem;">Pass ID: #${isSickInHostel ? 'MED' : 'OP'}-2026-00${leave.id}</div>
                </div>
            </div>

            <div class="outpass-verification-chain">
                <div class="chain-item">✓ Parent OTP Verified</div>
                <div class="chain-item">✓ Advisor Approved (Attendance Exemption)</div>
                <div class="chain-item">✓ HOD Authorized</div>
                <div class="chain-item" style="color:#1e40af; font-weight:700;">✓ Warden Cleared (${isSickInHostel ? 'Hostel Room Rest Active' : 'Gate Pass Active'})</div>
            </div>
        </div>
    `;

    if (window.QRCode && window.QRCode.generate) {
        try {
            const qrCanvas = window.QRCode.generate(securityUrl, {
                width: 170,
                height: 170,
                colorDark: '#0f172a',
                colorLight: '#ffffff'
            });
            const qrTarget = document.getElementById('outpassQrCanvasContainer');
            if (qrTarget) {
                qrTarget.innerHTML = '';
                qrTarget.appendChild(qrCanvas);
            }
        } catch (qrErr) {
            console.error("QR Generation error:", qrErr);
        }
    }
}
window.renderHostelOutpassModal = renderHostelOutpassModal;

/**
 * View Leave Letter in Official Letterhead Modal
 * @param {number} leaveId
 */
async function viewLeaveLetter(leaveId) {
    try {
        if (window.CampusDB) {
            const data = await window.CampusDB.getLeaveDetails(leaveId);
            if (data) {
                renderLeaveLetterModal(data);
                openModal('leaveLetterModal');
                return;
            }
        }
        const res = await fetch(`../backend/leave_request.php?action=get_leave_details&id=${leaveId}`);
        const data = await res.json();

        if (!data.success) {
            alert('Failed to load leave letter details.');
            return;
        }

        renderLeaveLetterModal(data);
        openModal('leaveLetterModal');

    } catch (err) {
        console.error(err);
        alert('Error fetching leave details: ' + err.message);
    }
}
window.viewLeaveLetter = viewLeaveLetter;

/**
 * Open Voice Approval Modal for Parent
 * @param {number} leaveId
 * @param {string} studentName
 * @param {string} regNo
 * @param {string} preferredLang
 */
function openParentVoiceApprovalModal(leaveId, studentName, regNo, preferredLang = 'ta') {
    const modal = document.getElementById('parentVoiceModal');
    if (!modal) return;

    document.getElementById('voiceApprovalLeaveId').value = leaveId;
    document.getElementById('voiceTargetStudent').textContent = `${studentName} (${regNo})`;
    document.getElementById('voiceVerifyResult').style.display = 'none';
    document.getElementById('recordedAudioInput').value = '';
    document.getElementById('spokenTranscriptInput').value = '';
    document.getElementById('matchScoreInput').value = '0';
    document.getElementById('liveTranscript').textContent = '"Press and hold the record button to speak your approval..."';

    const visualizer = document.getElementById('voiceApprovalVisualizer');
    const btnRecord = document.getElementById('btnStartVoiceApproval');
    const btnConfirm = document.getElementById('btnSubmitVoiceApproval');

    btnConfirm.disabled = true;
    let isRecording = false;

    btnRecord.onclick = async (e) => {
        e.preventDefault();
        if (!isRecording) {
            const started = await window.CampusVoice.startRecording(visualizer, (base64Audio, transcript) => {
                document.getElementById('recordedAudioInput').value = base64Audio;
                document.getElementById('spokenTranscriptInput').value = transcript;

                // Evaluate Biometric Match Score
                const score = window.CampusVoice.evaluateBiometricMatch(transcript, base64Audio);
                document.getElementById('matchScoreInput').value = score;

                // Show Verification Result
                const resultBox = document.getElementById('voiceVerifyResult');
                const scoreBar = document.getElementById('voiceScoreBar');
                const scoreText = document.getElementById('voiceScoreText');

                resultBox.style.display = 'block';
                scoreBar.style.width = `${score}%`;
                scoreText.innerHTML = `Voice Biometric Match Score: <strong>${score}%</strong>`;

                if (score >= 70.0) {
                    resultBox.className = 'alert alert-success';
                    resultBox.innerHTML = `✅ <strong>Voice Verified!</strong> Biometric Match: ${score}% (Threshold: 70%). You can now submit approval.`;
                    btnConfirm.disabled = false;
                } else {
                    resultBox.className = 'alert alert-danger';
                    resultBox.innerHTML = `❌ <strong>Verification Failed:</strong> Score ${score}% is below required threshold. Please record your approval clearly again.`;
                    btnConfirm.disabled = true;
                }

                btnRecord.innerHTML = '🔄 Re-Record Voice Approval';
                btnRecord.classList.remove('btn-danger');
                btnRecord.classList.add('btn-voice');
            });

            if (started) {
                isRecording = true;
                btnRecord.innerHTML = '⏹️ Stop Recording (Speaking...)';
                btnRecord.classList.remove('btn-voice');
                btnRecord.classList.add('btn-danger');
            }
        } else {
            window.CampusVoice.stopRecording();
            isRecording = false;
        }
    };

    openModal('parentVoiceModal');
}

/**
 * Submit Parent Voice Approval via AJAX
 */
async function submitParentApproval(action = 'approve') {
    const leaveId = document.getElementById('voiceApprovalLeaveId').value;
    const audioData = document.getElementById('recordedAudioInput').value;
    const transcript = document.getElementById('spokenTranscriptInput').value;
    const score = document.getElementById('matchScoreInput').value;
    const remarks = document.getElementById('parentRemarksInput') ? document.getElementById('parentRemarksInput').value : '';

    const submitBtn = document.getElementById('btnSubmitVoiceApproval');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Submitting Approval...';
    }

    try {
        const formData = new FormData();
        formData.append('leave_id', leaveId);
        formData.append('action', action);
        formData.append('recorded_audio', audioData);
        formData.append('spoken_transcript', transcript);
        formData.append('match_score', score);
        formData.append('remarks', remarks);

        const res = await fetch('../backend/parent_approval.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            alert(data.message);
            closeModal('parentVoiceModal');
            window.location.reload();
        } else {
            alert(`Approval Error: ${data.message}`);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Confirm Voice Approval';
            }
        }
    } catch (err) {
        console.error(err);
        alert('Failed to submit parent approval.');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Confirm Voice Approval';
        }
    }
}

/**
 * Handle Faculty Approvals (Advisor, HOD, Warden)
 * @param {string} endpoint - 'advisor_approval.php' | 'hod_approval.php' | 'warden_approval.php'
 * @param {number} leaveId
 * @param {string} action - 'approve' | 'reject'
 * @param {string} remarks
 */
async function processFacultyAction(endpoint, leaveId, action, remarks = '') {
    const confirmPrompt = action === 'approve' 
        ? 'Are you sure you want to APPROVE this leave request?' 
        : 'Are you sure you want to REJECT this leave request?';

    if (!confirm(confirmPrompt)) return;

    try {
        const formData = new FormData();
        formData.append('leave_id', leaveId);
        formData.append('action', action);
        formData.append('remarks', remarks);

        const res = await fetch(`../backend/${endpoint}`, {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert(`Error: ${data.message}`);
        }
    } catch (err) {
        console.error(err);
        alert('Server communication error.');
    }
}

/**
 * Helper to get CSS class for status badges
 */
function getStatusClass(status) {
    if (!status) return 'status-yellow';
    const s = status.toLowerCase();
    if (s.includes('approved') || s.includes('completed')) return 'status-green';
    if (s.includes('rejected')) return 'status-red';
    return 'status-yellow';
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

function renderApprovalsAudit(approvals) {
    if (!approvals || approvals.length === 0) {
        return '<p style="color:#64748b; font-size:0.85rem;">No stage approvals recorded yet.</p>';
    }

    let html = '<div style="display:flex; flex-direction:column; gap:0.5rem;">';
    approvals.forEach(a => {
        const isApp = a.action === 'approved';
        const roleTitle = a.approver_role.toUpperCase();
        const dateStr = new Date(a.action_timestamp).toLocaleString('en-GB', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' });
        html += `
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:0.65rem 1rem; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <strong>${roleTitle}:</strong> ${a.approver_name || 'Authorized Official'} 
                    <span style="color:#64748b; font-size:0.8rem; margin-left:0.5rem;">(${a.remarks || 'No remarks'})</span>
                </div>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <span class="status-badge ${isApp ? 'status-green' : 'status-red'}">${isApp ? 'Approved' : 'Rejected'}</span>
                    <span style="color:#94a3b8; font-size:0.75rem;">${dateStr}</span>
                </div>
            </div>
        `;
    });
    html += '</div>';
    return html;
}

/**
 * Generate and Display Scannable Digital Outpass with QR Code for Hostellers
 * @param {number} leaveId
 */
async function viewHostelOutpass(leaveId) {
    try {
        const res = await fetch(`../backend/leave_request.php?action=get_leave_details&id=${leaveId}`);
        const data = await res.json();

        if (!data.success) {
            alert('Failed to load outpass details.');
            return;
        }

        const leave = data.leave;
        const outpassModal = document.getElementById('hostelOutpassModal');
        const container = document.getElementById('outpassModalContent');

        if (!outpassModal || !container) return;

        const fromDateFormatted = new Date(leave.from_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        const toDateFormatted = new Date(leave.to_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
        const fromTime = leave.from_time ? leave.from_time.substring(0, 5) : '08:00';
        const toTime = leave.to_time ? leave.to_time.substring(0, 5) : '18:00';

        // Prepare scannable verification URL & payload for gate security
        const securityUrl = `${window.location.origin}/security_gate.html?pass_id=${leave.id}&reg=${encodeURIComponent(leave.register_number)}&status=VALID`;

        container.innerHTML = `
            <div class="outpass-container" id="printableOutpassCard">
                <div class="outpass-topbar">
                    <div>
                        <div class="outpass-topbar-brand">CampusFlow Digital Gate Pass</div>
                        <div style="font-size:0.75rem; color:#cbd5e1; letter-spacing:0.03em;">HOSTEL OUTPASS & SECURITY CLEARANCE</div>
                    </div>
                    <div class="outpass-security-badge">
                        <span>🛡️</span>
                        <span>OFFICIALLY VERIFIED</span>
                    </div>
                </div>

                <div class="outpass-body-grid">
                    <div class="outpass-student-details">
                        <div class="outpass-row">
                            <strong>Pass Reference ID:</strong>
                            <span style="font-family:monospace; font-weight:700; color:#1e40af;">#OP-2026-00${leave.id}</span>
                        </div>
                        <div class="outpass-row">
                            <strong>Hosteller Name:</strong>
                            <span><strong>${leave.student_name}</strong></span>
                        </div>
                        <div class="outpass-row">
                            <strong>Register Number:</strong>
                            <span>${leave.register_number}</span>
                        </div>
                        <div class="outpass-row">
                            <strong>Department & Class:</strong>
                            <span>${leave.department} (${leave.year || '3'}-${leave.section || 'A'})</span>
                        </div>
                        <div class="outpass-row">
                            <strong>Category:</strong>
                            <span style="color:#1e40af; font-weight:700;">${leave.leave_type}</span>
                        </div>
                        <div class="outpass-row">
                            <strong>Valid Exit Time:</strong>
                            <span style="color:#059669; font-weight:700;">${fromDateFormatted} @ ${fromTime}</span>
                        </div>
                        <div class="outpass-row">
                            <strong>Valid Entry / Return:</strong>
                            <span style="color:#dc2626; font-weight:700;">${toDateFormatted} @ ${toTime}</span>
                        </div>
                        <div class="outpass-row">
                            <strong>Destination:</strong>
                            <span>${escapeHtml(leave.destination_address)}</span>
                        </div>
                        <div class="outpass-row">
                            <strong>Parent Contact:</strong>
                            <span>${leave.emergency_contact || leave.parent_phone || 'N/A'}</span>
                        </div>
                    </div>

                    <!-- Scannable QR Code Frame -->
                    <div class="outpass-qr-frame">
                        <div id="outpassQrCanvasContainer" style="display:flex; justify-content:center; align-items:center; min-height:170px;"></div>
                        <div class="outpass-qr-caption">📷 Scan at Main Gate</div>
                        <div style="font-size:0.7rem; color:#64748b; margin-top:0.25rem;">Pass ID: #OP-2026-00${leave.id}</div>
                    </div>
                </div>

                <div class="outpass-verification-chain">
                    <div class="chain-item">✓ Parent OTP Verified</div>
                    <div class="chain-item">✓ Advisor Approved</div>
                    <div class="chain-item">✓ HOD Authorized</div>
                    <div class="chain-item" style="color:#1e40af; font-weight:700;">✓ Warden Cleared (Gate Pass Active)</div>
                </div>
            </div>
        `;

        // Generate QR code canvas
        if (window.QRCode && window.QRCode.generate) {
            try {
                const qrCanvas = window.QRCode.generate(securityUrl, {
                    width: 170,
                    height: 170,
                    colorDark: '#0f172a',
                    colorLight: '#ffffff'
                });
                const qrTarget = document.getElementById('outpassQrCanvasContainer');
                if (qrTarget) {
                    qrTarget.innerHTML = '';
                    qrTarget.appendChild(qrCanvas);
                }
            } catch (qrErr) {
                console.error("QR Generation error:", qrErr);
            }
        }

        openModal('hostelOutpassModal');

    } catch (err) {
        console.error("Outpass error:", err);
        alert('Error loading outpass details: ' + err.message);
    }
}
window.getStatusClass = getStatusClass;
window.escapeHtml = escapeHtml;
window.renderApprovalsAudit = renderApprovalsAudit;
window.viewHostelOutpass = viewHostelOutpass;
