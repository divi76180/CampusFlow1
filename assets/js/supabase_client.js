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
            const { data: users, error } = await sb
                .from('users')
                .select('*')
                .eq('username', username.trim())
                .limit(1);

            if (error) throw error;
            if (!users || users.length === 0) {
                return { success: false, message: 'Invalid username or credentials.' };
            }

            const user = users[0];

            // Fetch profile data based on role
            let profile = null;
            if (user.role === 'student') {
                const { data: std } = await sb.from('students').select('*').eq('user_id', user.id).limit(1);
                profile = std ? std[0] : null;
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
        const { data, error } = await sb
            .from('leave_requests')
            .select('*')
            .eq('student_id', studentId)
            .order('id', { ascending: false });
        if (error) { console.error(error); return []; }
        return data || [];
    }

    async function getAllLeaves() {
        const sb = initClient();
        if (!sb) return [];
        const { data, error } = await sb
            .from('leave_requests')
            .select(`
                *,
                students (
                    id, register_number, full_name, department, year, section, hostel_status, room_number, parent_id
                )
            `)
            .order('id', { ascending: false });
        if (error) { console.error(error); return []; }
        return data || [];
    }

    async function getLeaveDetails(leaveId) {
        const sb = initClient();
        if (!sb) return null;
        
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

        if (error || !leaves || leaves.length === 0) return null;
        const leave = leaves[0];

        // Fetch parent details
        let parent = null;
        if (leave.students && leave.students.parent_id) {
            const { data: par } = await sb.from('parents').select('*').eq('id', leave.students.parent_id).limit(1);
            parent = par ? par[0] : null;
        }

        // Fetch approvals
        const { data: approvals } = await sb.from('approvals').select('*').eq('leave_id', leaveId).order('id', { ascending: true });

        // Fetch voice verification
        const { data: voiceVerifs } = await sb.from('voice_verifications').select('*').eq('leave_id', leaveId).limit(1);

        return {
            leave: {
                ...leave,
                student_name: leave.students?.full_name || 'Student',
                register_number: leave.students?.register_number || '',
                department: leave.students?.department || '',
                year: leave.students?.year || 3,
                section: leave.students?.section || 'A',
                hostel_status: leave.students?.hostel_status || 'day_scholar',
                parent_name: parent?.full_name || '',
                parent_phone: parent?.phone_number || '',
                preferred_language: parent?.preferred_language || 'ta'
            },
            approvals: approvals || [],
            voice_verification: voiceVerifs ? voiceVerifs[0] : null
        };
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

    async function submitParentVoiceApproval(leaveId, parentId, transcript, score, remarks = '') {
        const sb = initClient();
        if (!sb) return { success: false, message: 'Supabase not connected.' };

        try {
            const isVerified = score >= 70.0;
            if (!isVerified) {
                return { success: false, message: 'Voice match score is below the 70% threshold.' };
            }

            // 1. Log Voice Verification
            await sb.from('voice_verifications').insert([{
                leave_id: leaveId,
                parent_id: parentId,
                spoken_transcript: transcript,
                match_score: score,
                is_verified: true,
                audio_path: `voice_audits/leave_${leaveId}_approved.webm`
            }]);

            // 2. Log Approval
            await sb.from('approvals').insert([{
                leave_id: leaveId,
                approver_role: 'parent',
                action: 'approved',
                remarks: remarks || `Parent Voice Verified (${score}% confidence)`
            }]);

            // 3. Update Leave Request Stage -> Advisor
            await sb.from('leave_requests').update({
                status: 'Parent Approved (Waiting for Advisor)',
                current_stage: 'advisor',
                updated_at: new Date().toISOString()
            }).eq('id', leaveId);

            return { success: true, message: 'Parent Voice Approval verified and recorded in Supabase!' };
        } catch (err) {
            return { success: false, message: err.message };
        }
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
            let username = formValues.username || formValues.register_number || formValues.phone;
            let email = formValues.email;
            let phone = formValues.phone || formValues.phone_number;

            if (role === 'student') {
                username = formValues.register_number;
            } else if (role === 'parent') {
                username = formValues.phone_number;
                email = `${formValues.phone_number}@parent.campusflow.edu`;
                phone = formValues.phone_number;
            } else if (['advisor', 'hod', 'warden'].includes(role)) {
                username = formValues.faculty_id;
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
                const { data: stdData, error: sErr } = await sb
                    .from('students')
                    .insert([{
                        user_id: newUser.id,
                        register_number: formValues.register_number,
                        full_name: formValues.full_name,
                        department: formValues.department,
                        year: parseInt(formValues.year || '3', 10),
                        section: formValues.section || 'A',
                        hostel_status: formValues.hostel_status || 'hosteller',
                        room_number: formValues.room_number || (formValues.hostel_status === 'hosteller' ? 'BH-204' : null)
                    }])
                    .select();
                if (sErr) throw sErr;
                profile = stdData[0];
            } else if (role === 'parent') {
                const { data: pData, error: pErr } = await sb
                    .from('parents')
                    .insert([{
                        user_id: newUser.id,
                        full_name: formValues.full_name,
                        phone_number: formValues.phone_number,
                        student_reg_no: formValues.student_reg_no,
                        preferred_language: formValues.preferred_language || 'ta'
                    }])
                    .select();
                if (pErr) throw pErr;
                profile = pData[0];
            } else if (['advisor', 'hod', 'warden'].includes(role)) {
                const { data: fData, error: fErr } = await sb
                    .from('faculty')
                    .insert([{
                        user_id: newUser.id,
                        faculty_id: formValues.faculty_id,
                        full_name: formValues.full_name,
                        department: formValues.department,
                        designation: formValues.designation || 'Faculty Member',
                        assigned_year: parseInt(formValues.assigned_year || '3', 10),
                        assigned_section: formValues.assigned_section || 'A',
                        hostel_block: formValues.hostel_block || null
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
            return { success: false, message: err.message || 'Signup failed.' };
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
        submitParentVoiceApproval,
        submitFacultyApproval
    };
}));
