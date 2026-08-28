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
                    <input type="text" name="register_number" id="signupRegisterNumber" class="form-control" placeholder="e.g. 25ITR009 / 21CS101" required oninput="autoDetectDepartmentFromRegNo(this.value)">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control" placeholder="student@campusflow.edu" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="tel" name="phone" class="form-control" placeholder="9876543201" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Department *</label>
                    <select name="department" id="signupDepartmentSelect" class="form-control form-select" required>
                        <option value="Information Technology">Information Technology (IT)</option>
                        <option value="Computer Science and Engineering" selected>Computer Science and Engineering (CSE)</option>
                        <option value="Artificial Intelligence and Data Science">Artificial Intelligence &amp; Data Science (AI &amp; DS)</option>
                        <option value="Electronics and Communication">Electronics &amp; Communication (ECE)</option>
                        <option value="Electrical and Electronics Engineering">Electrical &amp; Electronics (EEE)</option>
                        <option value="Mechanical Engineering">Mechanical Engineering (MECH)</option>
                        <option value="Civil Engineering">Civil Engineering (CIVIL)</option>
                        <option value="Mechatronics Engineering">Mechatronics Engineering (MTS)</option>
                        <option value="Biotechnology">Biotechnology (BT)</option>
                        <option value="Chemical Engineering">Chemical Engineering (CHEM)</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Academic Year *</label>
                    <select name="year" class="form-control form-select" required>
                        <option value="1">1st Year (Year 1)</option>
                        <option value="2">2nd Year (Year 2)</option>
                        <option value="3" selected>3rd Year (Year 3)</option>
                        <option value="4">4th Year (Year 4)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Class Section *</label>
                    <select name="section" class="form-control form-select" required>
                        <option value="A" selected>Section A</option>
                        <option value="B">Section B</option>
                        <option value="C">Section C</option>
                        <option value="D">Section D</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Hostel Status *</label>
                    <select name="hostel_status" id="signupHostelStatus" class="form-control form-select" required onchange="toggleHostelSignupFields(this.value)">
                        <option value="hosteller">Hosteller (Requires Warden Approval)</option>
                        <option value="day_scholar">Day Scholar (Skips Warden Stage)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Parent Phone Number (For Linking)</label>
                    <input type="tel" name="parent_phone" class="form-control" placeholder="e.g. 9876543210">
                </div>
            </div>

            <!-- Dynamic Hosteller Details (Hostel Name & Room Number) -->
            <div id="hostelDetailsGroup" class="form-row" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.85rem; border-radius: 8px; margin-bottom: 1rem;">
                <div class="form-group">
                    <label class="form-label">Hostel Name / Block *</label>
                    <select name="hostel_block" class="form-control form-select">
                        <optgroup label="👦 Boys Hostels">
                            <option value="Dheeran Boys Hostel">Dheeran Boys Hostel</option>
                            <option value="Ponnar Boys Hostel">Ponnar Boys Hostel</option>
                            <option value="Valluvar Boys Hostel">Valluvar Boys Hostel</option>
                        </optgroup>
                        <optgroup label="👧 Girls Hostels">
                            <option value="Bhavani Girls Hostel">Bhavani Girls Hostel</option>
                            <option value="Kaveri Girls Hostel">Kaveri Girls Hostel</option>
                            <option value="Amaravathi Girls Hostel">Amaravathi Girls Hostel</option>
                        </optgroup>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Room Number *</label>
                    <input type="text" name="room_number" class="form-control" placeholder="e.g. BH-304 / Room 102">
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
                <label class="form-label">Preferred Language for Voice Reading / Audio Letter *</label>
                <select name="preferred_language" class="form-control form-select" required>
                    <option value="ta">Tamil (தமிழ்)</option>
                    <option value="hi">Hindi (हिन्दी)</option>
                    <option value="te">Telugu (తెలుగు)</option>
                    <option value="ml">Malayalam (മലയാളം)</option>
                    <option value="kn">Kannada (ಕನ್ನಡ)</option>
                    <option value="en">English (Indian English)</option>
                </select>
            </div>

            <!-- SMS OTP Verification Security Banner -->
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 0.85rem 1rem; margin-top: 0.75rem; font-size: 0.85rem; color: #1e3a8a;">
                <div style="font-weight: 700; margin-bottom: 3px; display:flex; align-items:center; gap:6px;">
                    <span>📱</span> <span>SMS OTP Authorization Security</span>
                </div>
                <div style="color: #3b82f6; line-height:1.4;">
                    Your mobile number will receive a 6-digit secure SMS OTP whenever your ward applies for institutional leave.
                </div>
            </div>
        `;

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
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Department *</label>
                    <select name="department" class="form-control form-select" required>
                        <option value="Information Technology">Information Technology (IT)</option>
                        <option value="Computer Science and Engineering" selected>Computer Science and Engineering (CSE)</option>
                        <option value="Artificial Intelligence and Data Science">Artificial Intelligence &amp; Data Science (AI &amp; DS)</option>
                        <option value="Electronics and Communication">Electronics &amp; Communication (ECE)</option>
                        <option value="Electrical and Electronics Engineering">Electrical &amp; Electronics (EEE)</option>
                        <option value="Mechanical Engineering">Mechanical Engineering (MECH)</option>
                        <option value="Civil Engineering">Civil Engineering (CIVIL)</option>
                        <option value="Mechatronics Engineering">Mechatronics Engineering (MTS)</option>
                        <option value="Biotechnology">Biotechnology (BT)</option>
                        <option value="Chemical Engineering">Chemical Engineering (CHEM)</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Assigned Year *</label>
                    <select name="assigned_year" class="form-control form-select" required>
                        <option value="1">1st Year (Year 1)</option>
                        <option value="2">2nd Year (Year 2)</option>
                        <option value="3" selected>3rd Year (Year 3)</option>
                        <option value="4">4th Year (Year 4)</option>
                        <option value="ALL">All Years (Department-wide)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Assigned Section *</label>
                    <select name="assigned_section" class="form-control form-select" required>
                        <option value="A" selected>Section A</option>
                        <option value="B">Section B</option>
                        <option value="C">Section C</option>
                        <option value="D">Section D</option>
                        <option value="ALL">All Sections (Entire Year)</option>
                    </select>
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
                    <option value="Information Technology">Information Technology (IT)</option>
                    <option value="Computer Science and Engineering" selected>Computer Science and Engineering (CSE)</option>
                    <option value="Artificial Intelligence and Data Science">Artificial Intelligence &amp; Data Science (AI &amp; DS)</option>
                    <option value="Electronics and Communication">Electronics &amp; Communication (ECE)</option>
                    <option value="Electrical and Electronics Engineering">Electrical &amp; Electronics (EEE)</option>
                    <option value="Mechanical Engineering">Mechanical Engineering (MECH)</option>
                    <option value="Civil Engineering">Civil Engineering (CIVIL)</option>
                    <option value="Mechatronics Engineering">Mechatronics Engineering (MTS)</option>
                    <option value="Biotechnology">Biotechnology (BT)</option>
                    <option value="Chemical Engineering">Chemical Engineering (CHEM)</option>
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
                    <optgroup label="👦 Boys Hostels">
                        <option value="Dheeran Boys Hostel">Dheeran Boys Hostel</option>
                        <option value="Ponnar Boys Hostel">Ponnar Boys Hostel</option>
                        <option value="Valluvar Boys Hostel">Valluvar Boys Hostel</option>
                    </optgroup>
                    <optgroup label="👧 Girls Hostels">
                        <option value="Bhavani Girls Hostel">Bhavani Girls Hostel</option>
                        <option value="Kaveri Girls Hostel">Kaveri Girls Hostel</option>
                        <option value="Amaravathi Girls Hostel">Amaravathi Girls Hostel</option>
                    </optgroup>
                </select>
            </div>
        `;
    }
}

function toggleHostelSignupFields(status) {
    const group = document.getElementById('hostelDetailsGroup');
    if (group) {
        group.style.display = (status === 'hosteller') ? 'grid' : 'none';
        const inputs = group.querySelectorAll('input, select');
        inputs.forEach(inp => {
            if (status === 'hosteller') {
                inp.setAttribute('required', 'required');
            } else {
                inp.removeAttribute('required');
            }
        });
    }
}

function autoDetectDepartmentFromRegNo(regNo) {
    if (!regNo) return;
    const upper = regNo.toUpperCase().trim();
    const deptSelect = document.getElementById('signupDepartmentSelect') || document.querySelector('select[name="department"]');
    if (!deptSelect) return;

    if (upper.includes('ITR') || upper.includes('IT')) {
        deptSelect.value = 'Information Technology';
    } else if (upper.includes('CSR') || upper.includes('CSE') || upper.includes('CS')) {
        deptSelect.value = 'Computer Science and Engineering';
    } else if (upper.includes('ADR') || upper.includes('AIDS') || upper.includes('AD')) {
        deptSelect.value = 'Artificial Intelligence and Data Science';
    } else if (upper.includes('ECR') || upper.includes('ECE') || upper.includes('EC')) {
        deptSelect.value = 'Electronics and Communication';
    } else if (upper.includes('EER') || upper.includes('EEE') || upper.includes('EE')) {
        deptSelect.value = 'Electrical and Electronics Engineering';
    } else if (upper.includes('MER') || upper.includes('MECH') || upper.includes('ME')) {
        deptSelect.value = 'Mechanical Engineering';
    } else if (upper.includes('CIR') || upper.includes('CIVIL') || upper.includes('CE')) {
        deptSelect.value = 'Civil Engineering';
    } else if (upper.includes('MTR') || upper.includes('MTS')) {
        deptSelect.value = 'Mechatronics Engineering';
    } else if (upper.includes('BTR') || upper.includes('BT')) {
        deptSelect.value = 'Biotechnology';
    } else if (upper.includes('CHR') || upper.includes('CHEM')) {
        deptSelect.value = 'Chemical Engineering';
    }
}
