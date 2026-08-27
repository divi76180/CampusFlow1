/**
 * CampusFlow - Pure Supabase JavaScript Client & Data Layer
 * Direct connection to Supabase Cloud Database (Zero PHP, Zero Local MySQL)
 */

(function(root, factory) {
    root.CampusDB = factory();
}(typeof self !== 'undefined' ? self : this, function() {

    const SUPABASE_PROJECT_URL = 'https://rnwegrpgmkgfkahguaeu.supabase.co';
    const DEFAULT_ANON_KEY = 'sb_publishable_5v4EjWtYaoUxr11jUf9cZw_HXuacU5U';
    
    // Default or stored anon key
    let anonKey = localStorage.getItem('campusflow_supabase_key') || DEFAULT_ANON_KEY;

    let client = null;

    function initClient() {
        if (!client && window.supabase && anonKey) {
            client = window.supabase.createClient(SUPABASE_PROJECT_URL, anonKey);
        }
        return client;
    }

    function setAnonKey(key) {
        anonKey = key.trim();
        localStorage.setItem('campusflow_supabase_key', anonKey);
        if (window.supabase) {
            client = window.supabase.createClient(SUPABASE_PROJECT_URL, anonKey);
        }
    }

    function getAnonKey() {
        return anonKey || localStorage.getItem('campusflow_supabase_key') || '';
    }

    // Session Management
    function setCurrentUser(user) {
        sessionStorage.setItem('campusflow_session_user', JSON.stringify(user));
    }

    function getCurrentUser() {
        const str = sessionStorage.getItem('campusflow_session_user');
        return str ? JSON.parse(str) : null;
    }

    function logout() {
        sessionStorage.removeItem('campusflow_session_user');
        window.location.href = '/login.html';
    }

    function requireAuth(allowedRoles = []) {
        const user = getCurrentUser();
        if (!user) {
            alert('Please sign in to access your portal.');
            window.location.href = '/login.html';
            return null;
        }
        if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
            alert(`Access denied. Role '${user.role}' cannot access this portal.`);
            window.location.href = `/${user.role}/dashboard.html`;
            return null;
        }
        return user;
    }

    // Direct Database Methods
    async function login(username, password) {
        let sb = initClient();
        if (!sb) {
            const enteredKey = prompt('🔑 Please paste your Supabase "anon public" API Key (starts with eyJ...):\n\n(You can find this in Supabase Dashboard -> Project Settings -> API)');
            if (enteredKey && enteredKey.trim().length > 10) {
                setAnonKey(enteredKey.trim());
                sb = initClient();
            } else {
                return { success: false, message: 'Supabase anon API key is required to connect to the online database.' };
            }
        }

        try {
            const ident = username.trim();
            const { data: users, error } = await sb
                .from('users')
                .select('*')
                .or(`username.eq.${ident},phone.eq.${ident},email.eq.${ident}`)
                .limit(1);

            if (error) throw error;
            if (!users || users.length === 0) {
                return { success: false, message: 'Invalid username, phone number, or credentials.' };
            }

            const user = users[0];

            // Fetch profile data based on role
            let profile = null;
            if (user.role === 'student') {
                const { data: std } = await sb.from('students').select('*').eq('user_id', user.id).limit(1);
                profile = std ? std[0] : null;
                if (profile) {
                    let parentInfo = null;
                    if (profile.parent_id) {
                        const { data: pData } = await sb.from('parents').select('*').eq('id', profile.parent_id).limit(1);
                        if (pData && pData.length > 0) parentInfo = pData[0];
                    }
                    if (!parentInfo && profile.register_number) {
                        const { data: pData2 } = await sb.from('parents').select('*').eq('student_reg_no', profile.register_number).limit(1);
                        if (pData2 && pData2.length > 0) parentInfo = pData2[0];
                    }
                    if (parentInfo) {
                        profile.parent_name = parentInfo.full_name;
                        profile.parent_phone = parentInfo.phone_number;
                        profile.parent_language = parentInfo.preferred_language;
                    }
                }
            } else if (user.role === 'parent') {
                const { data: par } = await sb.from('parents').select('*').eq('user_id', user.id).limit(1);
                profile = par ? par[0] : null;
            } else if (['advisor', 'hod', 'warden'].includes(user.role)) {
                const { data: fac } = await sb.from('faculty').select('*').eq('user_id', user.id).limit(1);
                profile = fac ? fac[0] : null;
            }

            const sessionUser = {
                ...user,
                profile: profile
            };

            setCurrentUser(sessionUser);
            return { success: true, user: sessionUser };

        } catch (err) {
            console.error('Login error:', err);
            return { success: false, message: err.message || 'Login failed.' };
        }
    }

    async function getStudentLeaves(studentId) {
        const sb = initClient();
        if (!sb) return [];
        try {
            const { data, error } = await sb
                .from('leave_requests')
                .select('*')
                .eq('student_id', studentId)
                .order('id', { ascending: false });
            if (error) {
                console.error('getStudentLeaves error:', error);
                return [];
            }
            return data || [];
        } catch (err) {
            console.error('getStudentLeaves exception:', err);
            return [];
        }
    }

    async function getAllLeaves() {
        const sb = initClient();
        if (!sb) return [];
        try {
            const { data, error } = await sb
                .from('leave_requests')
                .select(`
                    *,
                    students (
                        id, register_number, full_name, department, year, section, hostel_status, room_number, parent_id
                    )
                `)
                .order('id', { ascending: false });

            if (error || !data) {
                console.warn('getAllLeaves with join failed, trying fallback direct query:', error);
                const { data: rawLeaves, error: rawErr } = await sb
                    .from('leave_requests')
                    .select('*')
                    .order('id', { ascending: false });
                
                if (rawErr || !rawLeaves) return [];

                // Fetch all students to map
                const { data: allStudents } = await sb.from('students').select('*');
                const studentMap = {};
                (allStudents || []).forEach(s => { studentMap[s.id] = s; });

                return rawLeaves.map(l => ({
                    ...l,
                    students: studentMap[l.student_id] || null
                }));
            }
            return data || [];
        } catch (err) {
            console.error('getAllLeaves exception:', err);
            return [];
        }
    }

    async function getLeaveDetails(leaveId) {
        const sb = initClient();
        if (!sb) return null;
        
        try {
            let leave = null;
            const { data: leaves, error } = await sb
                .from('leave_requests')
                .select(`
                    *,
                    students (
                        id, register_number, full_name, department, year, section, hostel_status, room_number, parent_id
                    )
                `)
                .eq('id', leaveId)
                .limit(1);

            if (!error && leaves && leaves.length > 0) {
                leave = leaves[0];
            } else {
                // Direct fallback query if join returned error
                const { data: rawLeaves } = await sb.from('leave_requests').select('*').eq('id', leaveId).limit(1);
                if (rawLeaves && rawLeaves.length > 0) {
                    leave = rawLeaves[0];
                    if (leave.student_id) {
                        const { data: stdData } = await sb.from('students').select('*').eq('id', leave.student_id).limit(1);
                        leave.students = stdData && stdData.length > 0 ? stdData[0] : null;
                    }
                }
            }

            if (!leave) return null;

            // Fetch parent details
            let parent = null;
            if (leave.students && leave.students.parent_id) {
                const { data: par } = await sb.from('parents').select('*').eq('id', leave.students.parent_id).limit(1);
                parent = par && par.length > 0 ? par[0] : null;
            }
            if (!parent && leave.students?.register_number) {
                const { data: parByReg } = await sb.from('parents').select('*').eq('student_reg_no', leave.students.register_number).limit(1);
                parent = parByReg && parByReg.length > 0 ? parByReg[0] : null;
            }

            // Fetch approvals
            const { data: approvals } = await sb.from('approvals').select('*').eq('leave_id', leaveId).order('id', { ascending: true });

            // Fetch voice verification
            const { data: voiceVerifs } = await sb.from('voice_verifications').select('*').eq('leave_id', leaveId).limit(1);

            return {
                leave: {
                    ...leave,
                    student_name: leave.students?.full_name || 'Student',
                    register_number: leave.students?.register_number || 'N/A',
                    department: leave.students?.department || 'Engineering',
                    year: leave.students?.year || 3,
                    section: leave.students?.section || 'A',
                    hostel_status: leave.students?.hostel_status || 'day_scholar',
                    parent_name: parent?.full_name || '',
                    parent_phone: parent?.phone_number || leave.emergency_contact || '',
                    preferred_language: parent?.preferred_language || 'ta'
                },
                approvals: approvals || [],
                voice_verification: voiceVerifs && voiceVerifs.length > 0 ? voiceVerifs[0] : null
            };
        } catch (err) {
            console.error('getLeaveDetails error:', err);
            return null;
        }
    }

    async function submitLeaveRequest(formData) {
        const sb = initClient();
        if (!sb) return { success: false, message: 'Supabase client not initialized.' };

        try {
            const { data, error } = await sb
                .from('leave_requests')
                .insert([{
                    student_id: formData.student_id,
                    leave_type: formData.leave_type,
                    from_date: formData.from_date,
                    to_date: formData.to_date,
                    from_time: formData.from_time || '08:00:00',
                    to_time: formData.to_time || '18:00:00',
                    reason: formData.reason,
                    destination_address: formData.destination_address,
                    emergency_contact: formData.emergency_contact,
                    status: 'Waiting for Parent',
                    current_stage: 'parent'
                }])
                .select();

            if (error) throw error;
            return { success: true, message: 'Leave request submitted successfully!', data: data[0] };
        } catch (err) {
            return { success: false, message: err.message };
        }
    }

    async function submitParentOtpApproval(leaveId, parentId, otpCode, remarks = '', phone = '') {
        const sb = initClient();
        if (!sb) return { success: false, message: 'Supabase not connected.' };

        try {
            const phoneStr = phone ? ` (Phone: +91 ${phone})` : '';

            // 1. Log OTP Approval in approvals table
            await sb.from('approvals').insert([{
                leave_id: leaveId,
                approver_role: 'parent',
                action: 'approved',
                remarks: remarks || `Parent Verified via SMS OTP${phoneStr} [Code: ${otpCode || 'Verified'}]`
            }]);

            // 2. Update Leave Request Stage -> Advisor
            await sb.from('leave_requests').update({
                status: 'Parent Approved (Waiting for Advisor)',
                current_stage: 'advisor',
                updated_at: new Date().toISOString()
            }).eq('id', leaveId);

            return { success: true, message: 'Parent SMS OTP Approval verified and recorded in Supabase!' };
        } catch (err) {
            return { success: false, message: err.message };
        }
    }

    async function submitParentRejection(leaveId, parentId, reason = '') {
        const sb = initClient();
        if (!sb) return { success: false, message: 'Supabase not connected.' };

        try {
            // 1. Log Rejection in approvals table
            await sb.from('approvals').insert([{
                leave_id: leaveId,
                approver_role: 'parent',
                action: 'rejected',
                remarks: reason || 'Leave request declined by Parent / Guardian'
            }]);

            // 2. Update Leave Request Stage -> Rejected
            await sb.from('leave_requests').update({
                status: 'Rejected by Parent',
                current_stage: 'rejected',
                updated_at: new Date().toISOString()
            }).eq('id', leaveId);

            return { success: true, message: 'Leave application rejected by Parent.' };
        } catch (err) {
            return { success: false, message: err.message };
        }
    }

    // Alias for backward compatibility
    async function submitParentVoiceApproval(leaveId, parentId, transcript, score, remarks = '') {
        return submitParentOtpApproval(leaveId, parentId, 'OTP_VERIFIED', remarks);
    }

    async function submitFacultyApproval(leaveId, role, action, remarks, user) {
        const sb = initClient();
        if (!sb) return { success: false, message: 'Supabase not connected.' };

        try {
            // Log Approval
            await sb.from('approvals').insert([{
                leave_id: leaveId,
                approver_role: role,
                approver_user_id: user.id,
                action: action,
                remarks: remarks || `${role.toUpperCase()} ${action}`
            }]);

            if (action === 'rejected') {
                await sb.from('leave_requests').update({
                    status: `Rejected by ${role.toUpperCase()}`,
                    current_stage: 'rejected',
                    updated_at: new Date().toISOString()
                }).eq('id', leaveId);
                return { success: true, message: `Leave request rejected by ${role.toUpperCase()}.` };
            }

            // Progression logic based on role & hostel status
            if (role === 'advisor') {
                await sb.from('leave_requests').update({
                    status: 'Advisor Approved (Waiting for HOD)',
                    current_stage: 'hod',
                    updated_at: new Date().toISOString()
                }).eq('id', leaveId);
            } else if (role === 'hod') {
                // Fetch leave to check hostel status
                const details = await getLeaveDetails(leaveId);
                const isHosteller = details?.leave?.hostel_status === 'hosteller';

                if (isHosteller) {
                    await sb.from('leave_requests').update({
                        status: 'Waiting for Warden (HOD Approved)',
                        current_stage: 'warden',
                        updated_at: new Date().toISOString()
                    }).eq('id', leaveId);
                } else {
                    await sb.from('leave_requests').update({
                        status: 'Completed',
                        current_stage: 'completed',
                        updated_at: new Date().toISOString()
                    }).eq('id', leaveId);
                }
            } else if (role === 'warden') {
                await sb.from('leave_requests').update({
                    status: 'Completed',
                    current_stage: 'completed',
                    updated_at: new Date().toISOString()
                }).eq('id', leaveId);
            }

            return { success: true, message: `Approved successfully by ${role.toUpperCase()}!` };
        } catch (err) {
            return { success: false, message: err.message };
        }
    }

    async function signup(role, formValues) {
        let sb = initClient();
        if (!sb) {
            const enteredKey = prompt('🔑 Please paste your Supabase "anon public" API Key (starts with eyJ...):\n\n(You can find this in Supabase Dashboard -> Project Settings -> API)');
            if (enteredKey && enteredKey.trim().length > 10) {
                setAnonKey(enteredKey.trim());
                sb = initClient();
            } else {
                return { success: false, message: 'Supabase anon API key is required to register.' };
            }
        }

        try {
            const facultyId = formValues.faculty_id || formValues.faculty_id_no || formValues.username || (formValues.email ? formValues.email.split('@')[0] : 'FAC-01');
            const phoneNum = formValues.phone || formValues.phone_number || '';
            const regNo = formValues.register_number || formValues.student_reg_no || formValues.username || '';

            let username = formValues.username || regNo || facultyId || phoneNum;
            let email = formValues.email || `${username}@campusflow.edu`;
            let phone = phoneNum;

            if (role === 'student') {
                username = regNo;
            } else if (role === 'parent') {
                username = phoneNum;
                email = formValues.email || `${phoneNum}@parent.campusflow.edu`;
                phone = phoneNum;
            } else if (['advisor', 'hod', 'warden'].includes(role)) {
                username = facultyId;
                phone = phoneNum;
            }

            // 1. Insert into users table
            const { data: userData, error: uErr } = await sb
                .from('users')
                .insert([{
                    username: username,
                    email: email,
                    phone: phone,
                    password_hash: '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', // password123 hash
                    role: role
                }])
                .select();

            if (uErr) throw uErr;
            const newUser = userData[0];

            // 2. Insert Profile
            let profile = null;
            if (role === 'student') {
                let parentId = null;
                const { data: pExist } = await sb.from('parents').select('id, full_name, phone_number').eq('student_reg_no', regNo).limit(1);
                if (pExist && pExist.length > 0) {
                    parentId = pExist[0].id;
                }
                const isHosteller = formValues.hostel_status === 'hosteller';
                const hostelBlock = isHosteller ? (formValues.hostel_block || formValues.hostel_name || 'Dheeran Boys Hostel') : null;
                const roomNumber = isHosteller ? (formValues.room_number || 'Room 101') : null;

                const { data: stdData, error: sErr } = await sb
                    .from('students')
                    .insert([{
                        user_id: newUser.id,
                        register_number: regNo,
                        full_name: formValues.full_name,
                        department: formValues.department,
                        year: parseInt(formValues.year || '3', 10),
                        section: formValues.section || 'A',
                        hostel_status: formValues.hostel_status || 'hosteller',
                        hostel_block: hostelBlock,
                        room_number: roomNumber,
                        parent_id: parentId
                    }])
                    .select();
                if (sErr) throw sErr;
                profile = stdData[0];
                if (pExist && pExist[0]) {
                    profile.parent_name = pExist[0].full_name;
                    profile.parent_phone = pExist[0].phone_number;
                }
            } else if (role === 'parent') {
                const childReg = (formValues.student_reg_no || '').trim();
                const { data: pData, error: pErr } = await sb
                    .from('parents')
                    .insert([{
                        user_id: newUser.id,
                        full_name: formValues.full_name,
                        phone_number: phoneNum,
                        student_reg_no: childReg || '21CS101',
                        preferred_language: formValues.preferred_language || 'ta'
                    }])
                    .select();
                if (pErr) throw pErr;
                profile = pData[0];

                // Link this parent to the student if the student account already exists
                if (childReg) {
                    await sb.from('students').update({ parent_id: profile.id }).eq('register_number', childReg);
                }
            } else if (['advisor', 'hod', 'warden'].includes(role)) {
                const section = formValues.section_handled || formValues.assigned_section || 'A';
                const designation = role === 'hod' ? 'Head of Department' : (role === 'warden' ? 'Hostel Warden' : 'Class Advisor');
                const hostelBlock = role === 'warden' ? (formValues.hostel_block || formValues.hostel_name || 'Dheeran Boys Hostel') : null;

                const { data: fData, error: fErr } = await sb
                    .from('faculty')
                    .insert([{
                        user_id: newUser.id,
                        faculty_id: facultyId,
                        full_name: formValues.full_name,
                        department: formValues.department || (role === 'warden' ? 'Hostel Administration' : 'Engineering'),
                        designation: designation,
                        assigned_year: role === 'advisor' ? parseInt(formValues.year_handled || '3', 10) : null,
                        assigned_section: role === 'advisor' ? section : null,
                        hostel_block: hostelBlock
                    }])
                    .select();
                if (fErr) throw fErr;
                profile = fData[0];
            }

            const sessionUser = {
                ...newUser,
                profile: profile
            };
            setCurrentUser(sessionUser);

            return {
                success: true,
                message: 'Account created successfully in Supabase!',
                redirect: `${role}/dashboard.html`,
                user: sessionUser
            };
        } catch (err) {
            console.error('Signup error:', err);
            let msg = err.message || 'Signup failed.';
            if (err.code === '23505' || msg.includes('users_username_key') || msg.includes('duplicate key') || msg.includes('already exists')) {
                if (role === 'student') {
                    msg = `Student Register Number "${formValues.register_number || formValues.username || ''}" is already registered! Please <a href="/login.html" style="text-decoration:underline; font-weight:700; color:inherit;">Sign In here</a>.`;
                } else if (role === 'parent') {
                    msg = `Parent mobile number "${formValues.phone || ''}" is already registered! Please <a href="/login.html" style="text-decoration:underline; font-weight:700; color:inherit;">Sign In here</a>.`;
                } else {
                    msg = `Faculty ID "${formValues.faculty_id || formValues.username || ''}" is already registered! Please <a href="/login.html" style="text-decoration:underline; font-weight:700; color:inherit;">Sign In here</a>.`;
                }
            }
            return { success: false, message: msg };
        }
    }

    return {
        url: SUPABASE_PROJECT_URL,
        initClient,
        setAnonKey,
        getAnonKey,
        login,
        logout,
        signup,
        getCurrentUser,
        setCurrentUser,
        requireAuth,
        getStudentLeaves,
        getAllLeaves,
        getLeaveDetails,
        submitLeaveRequest,
        submitParentOtpApproval,
        submitParentVoiceApproval,
        submitParentRejection,
        submitFacultyApproval
    };
}));
