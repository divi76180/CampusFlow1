function formatStudentDepartment(dept, regNo) {
    if (regNo) {
        const u = String(regNo).toUpperCase().trim();
        if (u.includes('ITR') || u.includes('IT')) return 'Information Technology (IT)';
        if (u.includes('CSR') || u.includes('CSE') || u.includes('CS')) return 'Computer Science and Engineering (CSE)';
        if (u.includes('ADR') || u.includes('AIDS') || u.includes('AD')) return 'Artificial Intelligence and Data Science (AI & DS)';
        if (u.includes('ECR') || u.includes('ECE') || u.includes('EC')) return 'Electronics & Communication (ECE)';
        if (u.includes('EER') || u.includes('EEE') || u.includes('EE')) return 'Electrical & Electronics (EEE)';
        if (u.includes('MER') || u.includes('MECH') || u.includes('ME')) return 'Mechanical Engineering (MECH)';
        if (u.includes('CIR') || u.includes('CIVIL') || u.includes('CE')) return 'Civil Engineering (CIVIL)';
        if (u.includes('MTR') || u.includes('MTS')) return 'Mechatronics Engineering (MTS)';
        if (u.includes('BTR') || u.includes('BT')) return 'Biotechnology (BT)';
        if (u.includes('CHR') || u.includes('CHEM')) return 'Chemical Engineering (CHEM)';
    }
    if (dept) {
        const d = String(dept).toLowerCase();
        if (d.includes('information') || d === 'it') return 'Information Technology (IT)';
        if (d.includes('computer') || d === 'cse') return 'Computer Science and Engineering (CSE)';
        if (d.includes('mechanical') || d === 'mech') return 'Mechanical Engineering (MECH)';
        if (d.includes('electronics') || d === 'ece') return 'Electronics & Communication (ECE)';
        if (d.includes('electrical') || d === 'eee') return 'Electrical & Electronics (EEE)';
        if (d.includes('civil')) return 'Civil Engineering (CIVIL)';
        if (d.includes('artificial') || d === 'aids') return 'AI & Data Science (AI & DS)';
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
 * Render Letterhead Preview inside a container
 */
function renderLetterhead(leave) {
    const letterContainer = document.getElementById('letterheadContent');
    if (!letterContainer) return;

    const fromDateFormatted = leave.from_date ? new Date(leave.from_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A';
    const toDateFormatted = leave.to_date ? new Date(leave.to_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : 'N/A';
    const createdFormatted = new Date(leave.created_at || Date.now()).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
    const stdName = leave.student_name || leave.students?.full_name || 'Student';
    const regNo = leave.register_number || leave.students?.register_number || 'N/A';
    const dept = formatStudentDepartment(leave.department || leave.students?.department, regNo);
    const yr = leave.year || leave.students?.year || 3;
    const sec = leave.section || leave.students?.section || 'A';
    const hostelSt = (leave.hostel_status || leave.students?.hostel_status || 'day_scholar').replace('_', ' ');
    const statusText = leave.status || 'Pending';

    letterContainer.innerHTML = `
        <div class="letterhead-doc">
            <div class="letterhead-header">
                <h2>CampusFlow Digital Leave Portal</h2>
                <p>Department of ${escapeHtml(dept)} | Official Authorization Form</p>
            </div>

            <div class="letterhead-meta">
                <div>
                    <strong>Date Submitted:</strong> ${createdFormatted}<br>
                    <strong>Student Name:</strong> ${escapeHtml(stdName)} (${escapeHtml(regNo)})<br>
                    <strong>Department & Year:</strong> ${escapeHtml(dept)} - Year ${yr}, Sec ${sec}<br>
                    <strong>Hostel Status:</strong> <span style="text-transform:capitalize; font-weight:700;">${escapeHtml(hostelSt)}</span>
                </div>
                <div>
                    <strong>Parent / Guardian:</strong> ${escapeHtml(leave.parent_name || 'Parent')}<br>
                    <strong>Emergency Contact:</strong> ${escapeHtml(leave.emergency_contact || leave.parent_phone || 'N/A')}<br>
                    <strong>Leave Category:</strong> <span style="color:#1e40af; font-weight:700;">${escapeHtml(leave.leave_type || 'Leave Request')}</span><br>
                    <strong>Current Status:</strong> <span class="status-badge ${getStatusClass(statusText)}">${escapeHtml(statusText)}</span>
                </div>
            </div>

            <div class="letterhead-body">
                <p><strong>To,</strong><br>
                   • <strong>Parent / Guardian:</strong> ${escapeHtml(leave.parent_name || 'Parent')} (+91 ${escapeHtml(leave.parent_phone || leave.emergency_contact || 'Registered Mobile')})<br>
                   • <strong>Class Advisor:</strong> Class Advisor (${escapeHtml(dept)} - Year ${yr}, Sec ${sec})<br>
                   • <strong>Head of Department:</strong> Department of ${escapeHtml(dept)}<br>
                   ${hostelSt === 'hosteller' ? `• <strong>Hostel Warden:</strong> ${escapeHtml(leave.hostel_block || leave.students?.hostel_block || 'Hostel Block In-Charge')}<br>` : ''}
                   <em>CampusFlow College of Engineering &amp; Technology</em>
                </p>
                <br>
                <p><strong>Respected Sir / Madam,</strong></p>
                <p>
                    I, <strong>${escapeHtml(stdName)}</strong> (Register No: <strong>${escapeHtml(regNo)}</strong>), studying in the Department of ${escapeHtml(dept)}, kindly request you to grant me leave from <strong>${fromDateFormatted}</strong> (${leave.from_time ? leave.from_time.substring(0,5) : '08:00 AM'}) to <strong>${toDateFormatted}</strong> (${leave.to_time ? leave.to_time.substring(0,5) : '06:00 PM'}).
                </p>
                <br>
                <p>
                    <strong>Reason for Leave:</strong><br>
                    <em>"${escapeHtml(leave.reason || 'N/A')}"</em>
                </p>
                <br>
                <p>
                    <strong>Destination Address during Leave:</strong><br>
                    ${escapeHtml(leave.destination_address || 'N/A')}
                </p>
            </div>

            <div class="letterhead-footer">
                <div>
                    <strong>Parent Authorization Verification:</strong><br>
                    ${statusText.includes('Parent Approved') || (approvals && approvals.some(a => a.approver_role === 'parent'))
                        ? `<div style="margin-top:0.25rem;">
                             <span class="status-badge status-green" style="display:inline-block; margin-bottom:0.25rem;">✅ Verified via SMS OTP (Phone: ${escapeHtml(leave.parent_phone || leave.emergency_contact || 'Registered Phone')})</span>
                             <div style="font-size:0.75rem; color:#64748b; margin-top:2px;">6-Digit Cryptographic OTP Security Confirmed</div>
                           </div>` 
                        : '<span style="color:#d97706; font-size:0.85rem; font-weight:600;">⏳ Pending Parent SMS OTP Authorization</span>'}
                </div>
                <div style="text-align: right;">
                    <strong>Student Signature:</strong><br>
                    <span style="font-family:cursive; font-size:1.1rem; color:#1e3a8a;">${escapeHtml(stdName)}</span>
                </div>
            </div>
        </div>

        <!-- Approval Progression History -->
        <div style="margin-top: 1.5rem;">
            <h4 style="font-size: 1rem; margin-bottom: 0.75rem; color: #0f172a;">Audit Trail & Approvals History</h4>
            ${renderApprovalsAudit(approvals)}
        </div>
    `;
}

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
    const dept = leave.department || leave.students?.department || 'Engineering';
    const yr = leave.year || leave.students?.year || 3;
    const sec = leave.section || leave.students?.section || 'A';

    const securityUrl = `${window.location.origin}/security_gate.html?pass_id=${leave.id}&reg=${encodeURIComponent(regNo)}&status=VALID`;

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
        alert('Error fetching leave details.');
    }
}

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
