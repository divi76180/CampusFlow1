/**
 * CampusFlow - Dynamic Multi-Role Signup & Authentication Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Dynamic Role Switcher on Signup Page
    const roleTabs = document.querySelectorAll('.role-tab-btn');
    const dynamicFieldsContainer = document.getElementById('dynamicRoleFields');
    const roleInputHidden = document.getElementById('selectedRoleInput');

    if (roleTabs.length > 0 && dynamicFieldsContainer) {
        roleTabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                roleTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                const role = tab.getAttribute('data-role');
                if (roleInputHidden) roleInputHidden.value = role;
                renderSignupFormFields(role, dynamicFieldsContainer);
            });
        });

        // Initialize default role (Student)
        renderSignupFormFields('student', dynamicFieldsContainer);
    }

    // Check if opened via file:// protocol instead of http web server
    const isFileProtocol = window.location.protocol === 'file:';
    const apiBase = isFileProtocol ? 'http://127.0.0.1:8080/' : '';

    if (isFileProtocol) {
        const authCard = document.querySelector('.auth-card');
        if (authCard) {
            const notice = document.createElement('div');
            notice.className = 'alert alert-warning';
            notice.style.marginBottom = '1.5rem';
            notice.innerHTML = `
                <div>
                    <strong>⚠️ Local Server Notice:</strong><br>
                    You opened this page via <code>file://</code>. Please click below to open via the active PHP backend server:
                    <div style="margin-top: 0.5rem;">
                        <a href="http://127.0.0.1:8080/login.html" class="btn btn-primary btn-sm">Open http://127.0.0.1:8080/login.html</a>
                    </div>
                </div>
            `;
            authCard.insertBefore(notice, authCard.firstChild);
        }
    }

    // 2. Signup Form Submission Handler (Direct Supabase)
    const signupForm = document.getElementById('signupForm');
    if (signupForm) {
        signupForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = signupForm.querySelector('button[type="submit"]');
            const alertBox = document.getElementById('authAlert');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Registering in Supabase...';
            if (alertBox) alertBox.style.display = 'none';

            const formData = new FormData(signupForm);
            const formValues = {};
            formData.forEach((val, key) => { formValues[key] = val; });
            const role = formValues.role || 'student';

            // Direct Supabase Registration
            if (window.CampusDB) {
                const sbRes = await window.CampusDB.signup(role, formValues);
                if (sbRes.success) {
                    if (alertBox) {
                        alertBox.className = 'alert alert-success';
                        alertBox.innerHTML = `<strong>Success!</strong> ${sbRes.message} Redirecting to your dashboard...`;
                        alertBox.style.display = 'block';
                    }
                    setTimeout(() => {
                        window.location.href = sbRes.redirect;
                    }, 1000);
                    return;
                } else if (!sbRes.message.includes('Supabase client not initialized')) {
                    if (alertBox) {
                        alertBox.className = 'alert alert-danger';
                        alertBox.innerHTML = `<strong>Error:</strong> ${sbRes.message}`;
                        alertBox.style.display = 'block';
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Create Account →';
                    return;
                }
            }

            // Fallback to PHP backend
            try {
                const res = await fetch(`${apiBase}backend/signup.php`, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    if (alertBox) {
                        alertBox.className = 'alert alert-success';
                        alertBox.innerHTML = `<strong>Success!</strong> ${data.message}`;
                        alertBox.style.display = 'block';
                    }
                    setTimeout(() => {
                        window.location.href = isFileProtocol ? `http://127.0.0.1:8080/${data.redirect || 'login.html'}` : (data.redirect || 'login.html');
                    }, 1000);
                } else {
                    if (alertBox) {
                        alertBox.className = 'alert alert-danger';
                        alertBox.innerHTML = `<strong>Error:</strong> ${data.message}`;
                        alertBox.style.display = 'block';
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Create Account →';
                }
            } catch (err) {
                console.error(err);
                if (alertBox) {
                    alertBox.className = 'alert alert-danger';
                    alertBox.innerHTML = '<strong>Error:</strong> Please ensure your Supabase API key is configured.';
                    alertBox.style.display = 'block';
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Create Account →';
            }
        });
    }

    // 3. Login Form Submission Handler (Supabase Direct + Fallback)
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            const alertBox = document.getElementById('loginAlert');

            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Authenticating via Supabase...';
            if (alertBox) alertBox.style.display = 'none';

            const identifier = document.getElementById('loginIdentifier').value.trim();
            const password = document.getElementById('loginPassword').value.trim();

            // Direct Supabase Authentication
            if (window.CampusDB) {
                const sbRes = await window.CampusDB.login(identifier, password);
                if (sbRes.success) {
                    if (alertBox) {
                        alertBox.className = 'alert alert-success';
                        alertBox.innerHTML = `<strong>Success!</strong> Welcome ${sbRes.user.profile?.full_name || sbRes.user.username}. Redirecting to ${sbRes.user.role} portal...`;
                        alertBox.style.display = 'block';
                    }
                    setTimeout(() => {
                        window.location.href = `${sbRes.user.role}/dashboard.html`;
                    }, 500);
                    return;
                } else {
                    if (alertBox) {
                        alertBox.className = 'alert alert-danger';
                        alertBox.innerHTML = `<strong>Error:</strong> ${sbRes.message}`;
                        alertBox.style.display = 'block';
                    }
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Sign In to Dashboard →';
                    return;
                }
            }
        });
    }
});

/**
 * Render dynamic signup form fields per role
 * @param {string} role - 'student' | 'parent' | 'advisor' | 'hod' | 'warden'
 * @param {HTMLElement} container
 */
function renderSignupFormFields(role, container) {
    if (role === 'student') {
        container.innerHTML = `
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g. Rahul Sharma" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Student ID / Register Number *</label>
                    <input type="text" name="register_number" class="form-control" placeholder="e.g. 21CS101" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" placeholder="rahul@campusflow.edu" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" placeholder="9876543201" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <select name="department" class="form-control form-select" required>
                        <option value="Computer Science and Engineering">Computer Science and Engineering (CSE)</option>
                        <option value="Electronics and Communication">Electronics & Communication (ECE)</option>
                        <option value="Information Technology">Information Technology (IT)</option>
                        <option value="Mechanical Engineering">Mechanical Engineering (MECH)</option>
                        <option value="Civil Engineering">Civil Engineering (CIVIL)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Year & Section *</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <select name="year" class="form-control form-select" required>
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3" selected>Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                        <select name="section" class="form-control form-select" required>
                            <option value="A" selected>Section A</option>
                            <option value="B">Section B</option>
                            <option value="C">Section C</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Hostel Status *</label>
                    <select name="hostel_status" class="form-control form-select" required>
                        <option value="hosteller">Hosteller (Requires Warden Approval)</option>
                        <option value="day_scholar">Day Scholar (Skips Warden Stage)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Parent Phone / ID (For Linking)</label>
                    <input type="text" name="parent_phone" class="form-control" placeholder="e.g. 9876543210">
                </div>
            </div>
        `;
    } else if (role === 'parent') {
        container.innerHTML = `
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Parent / Guardian Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g. Suresh Sharma" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number * (Primary Login)</label>
                    <input type="tel" name="phone" class="form-control" placeholder="e.g. 9876543210" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email Address (Optional)</label>
                    <input type="email" name="email" class="form-control" placeholder="Optional email address">
                </div>
                <div class="form-group">
                    <label class="form-label">Child's Student ID / Reg No *</label>
                    <input type="text" name="student_reg_no" class="form-control" placeholder="e.g. 21CS101" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Preferred Spoken Language for Letter Voice Reading *</label>
                <select name="preferred_language" class="form-control form-select" required>
                    <option value="ta">Tamil (தமிழ்)</option>
                    <option value="hi">Hindi (हिन्दी)</option>
                    <option value="te">Telugu (తెలుగు)</option>
                    <option value="ml">Malayalam (മലയാളം)</option>
                    <option value="kn">Kannada (ಕನ್ನಡ)</option>
                    <option value="en">English (Indian English)</option>
                </select>
            </div>
            
            <!-- Voice Sample Enrollment Section -->
            <div class="voice-enrollment-box" id="voiceEnrollmentContainer">
                <div style="font-weight: 700; color: #1e3a8a; margin-bottom: 0.35rem;">
                    🎙️ Voice Sample Registration for Biometric Approval
                </div>
                <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 0.85rem;">
                    Please record a 3-second consent phrase to enroll your voice profile for future leave verifications.
                </p>
                <div class="audio-visualizer" id="enrollVisualizer">
                    <div class="wave-bar"></div><div class="wave-bar"></div>
                    <div class="wave-bar"></div><div class="wave-bar"></div>
                    <div class="wave-bar"></div><div class="wave-bar"></div>
                    <div class="wave-bar"></div><div class="wave-bar"></div>
                </div>
                <button type="button" class="btn btn-voice btn-sm" id="btnRecordSample">
                    🔴 Record Voice Sample
                </button>
                <input type="hidden" name="voice_sample_data" id="voiceSampleData">
                <div class="voice-status-text" id="voiceEnrollStatus">Voice sample: Not yet recorded</div>
            </div>
        `;
        initVoiceEnrollmentWidget();

    } else if (role === 'advisor') {
        container.innerHTML = `
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g. Dr. A. Ramanathan" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Faculty ID *</label>
                    <input type="text" name="faculty_id" class="form-control" placeholder="e.g. FAC-CS-01" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" placeholder="advisor.cs@campusflow.edu" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" placeholder="9876543203" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <select name="department" class="form-control form-select" required>
                        <option value="Computer Science and Engineering">Computer Science and Engineering</option>
                        <option value="Electronics and Communication">Electronics & Communication</option>
                        <option value="Information Technology">Information Technology</option>
                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Class / Section Handled *</label>
                    <input type="text" name="section_handled" class="form-control" placeholder="e.g. 3-A or Year 3 Section A" required>
                </div>
            </div>
        `;
    } else if (role === 'hod') {
        container.innerHTML = `
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">HOD Full Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g. Dr. K. Meenakshi" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Faculty ID *</label>
                    <input type="text" name="faculty_id" class="form-control" placeholder="e.g. HOD-CSE-01" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" placeholder="hod.cse@campusflow.edu" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" placeholder="9876543204" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Department *</label>
                <select name="department" class="form-control form-select" required>
                    <option value="Computer Science and Engineering">Computer Science and Engineering</option>
                    <option value="Electronics and Communication">Electronics & Communication</option>
                    <option value="Information Technology">Information Technology</option>
                    <option value="Mechanical Engineering">Mechanical Engineering</option>
                    <option value="Civil Engineering">Civil Engineering</option>
                </select>
            </div>
        `;
    } else if (role === 'warden') {
        container.innerHTML = `
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Warden Full Name *</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g. Col. S. Balaji" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Faculty / Warden ID *</label>
                    <input type="text" name="faculty_id" class="form-control" placeholder="e.g. WARDEN-BH-01" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" placeholder="warden.bh@campusflow.edu" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" placeholder="9876543205" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Hostel Block In-Charge *</label>
                <select name="hostel_block" class="form-control form-select" required>
                    <option value="Kaveri Boys Hostel (BH-1)">Kaveri Boys Hostel (BH-1)</option>
                    <option value="Vaigai Boys Hostel (BH-2)">Vaigai Boys Hostel (BH-2)</option>
                    <option value="Thamirabarani Girls Hostel (GH-1)">Thamirabarani Girls Hostel (GH-1)</option>
                    <option value="Ganga Girls Hostel (GH-2)">Ganga Girls Hostel (GH-2)</option>
                </select>
            </div>
        `;
    }
}

/**
 * Initialize Voice Sample Enrollment Recorder in Parent Signup
 */
function initVoiceEnrollmentWidget() {
    const btnRecord = document.getElementById('btnRecordSample');
    const visualizer = document.getElementById('enrollVisualizer');
    const statusText = document.getElementById('voiceEnrollStatus');
    const hiddenData = document.getElementById('voiceSampleData');
    const boxContainer = document.getElementById('voiceEnrollmentContainer');

    if (!btnRecord) return;

    let isRecording = false;

    btnRecord.addEventListener('click', async (e) => {
        e.preventDefault();
        if (!isRecording) {
            const started = await window.CampusVoice.startRecording(visualizer, (base64Audio, transcript) => {
                hiddenData.value = base64Audio;
                statusText.innerHTML = '✅ Voice sample successfully enrolled and analyzed!';
                statusText.style.color = '#059669';
                boxContainer.classList.add('enrolled');
                btnRecord.innerHTML = '🔄 Re-record Sample';
                btnRecord.classList.remove('btn-danger');
                btnRecord.classList.add('btn-voice');
            });

            if (started) {
                isRecording = true;
                btnRecord.innerHTML = '⏹️ Stop Recording (Speaking...)';
                btnRecord.classList.remove('btn-voice');
                btnRecord.classList.add('btn-danger');
                statusText.innerHTML = 'Recording voice profile... speak naturally for 3-5 seconds.';
                statusText.style.color = '#dc2626';

                // Automatically stop after 5 seconds
                setTimeout(() => {
                    if (isRecording) {
                        window.CampusVoice.stopRecording();
                        isRecording = false;
                    }
                }, 5000);
            }
        } else {
            window.CampusVoice.stopRecording();
            isRecording = false;
        }
    });
}
