-- ==============================================================================
-- CampusFlow: Digital Leave Approval Portal
-- Supabase (PostgreSQL) Database Schema & Initial Seed Data
-- ==============================================================================

-- 1. Clean Up Existing Tables (if re-running)
DROP TABLE IF EXISTS notifications CASCADE;
DROP TABLE IF EXISTS voice_verifications CASCADE;
DROP TABLE IF EXISTS voice_samples CASCADE;
DROP TABLE IF EXISTS approvals CASCADE;
DROP TABLE IF EXISTS leave_requests CASCADE;
DROP TABLE IF EXISTS faculty CASCADE;
DROP TABLE IF EXISTS parents CASCADE;
DROP TABLE IF EXISTS students CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- 2. Create Users Table (Authentication & Role Assignment)
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    username VARCHAR(60) UNIQUE NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('student', 'parent', 'advisor', 'hod', 'warden')),
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 3. Create Students Table
CREATE TABLE students (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT UNIQUE NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    register_number VARCHAR(30) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    year INT NOT NULL CHECK (year BETWEEN 1 AND 5),
    section VARCHAR(10) NOT NULL DEFAULT 'A',
    hostel_status VARCHAR(20) NOT NULL CHECK (hostel_status IN ('hosteller', 'day_scholar')),
    room_number VARCHAR(30),
    parent_id BIGINT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 4. Create Parents Table
CREATE TABLE parents (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT UNIQUE NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    student_reg_no VARCHAR(30) NOT NULL,
    preferred_language VARCHAR(10) NOT NULL DEFAULT 'ta',
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- Add Foreign Key from students to parents
ALTER TABLE students 
ADD CONSTRAINT fk_student_parent FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE SET NULL;

-- 5. Create Faculty Table (Advisors, HODs, Wardens)
CREATE TABLE faculty (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT UNIQUE NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    faculty_id VARCHAR(30) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    department VARCHAR(100) NOT NULL,
    designation VARCHAR(100) NOT NULL,
    assigned_year INT,
    assigned_section VARCHAR(10),
    hostel_block VARCHAR(50),
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 6. Create Leave Requests Table
CREATE TABLE leave_requests (
    id BIGSERIAL PRIMARY KEY,
    student_id BIGINT NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    leave_type VARCHAR(50) NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    from_time TIME DEFAULT '08:00:00',
    to_time TIME DEFAULT '18:00:00',
    reason TEXT NOT NULL,
    destination_address TEXT NOT NULL,
    emergency_contact VARCHAR(20),
    status VARCHAR(50) NOT NULL DEFAULT 'Waiting for Parent',
    current_stage VARCHAR(30) NOT NULL DEFAULT 'parent' CHECK (current_stage IN ('parent', 'advisor', 'hod', 'warden', 'completed', 'rejected')),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 7. Create Approvals Audit Trail Table
CREATE TABLE approvals (
    id BIGSERIAL PRIMARY KEY,
    leave_id BIGINT NOT NULL REFERENCES leave_requests(id) ON DELETE CASCADE,
    approver_role VARCHAR(20) NOT NULL CHECK (approver_role IN ('parent', 'advisor', 'hod', 'warden')),
    approver_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(20) NOT NULL CHECK (action IN ('approved', 'rejected')),
    remarks TEXT,
    action_timestamp TIMESTAMPTZ DEFAULT NOW()
);

-- 8. Create Voice Samples Table (Parent Biometric Registration)
CREATE TABLE voice_samples (
    id BIGSERIAL PRIMARY KEY,
    parent_id BIGINT NOT NULL REFERENCES parents(id) ON DELETE CASCADE,
    audio_sample_path VARCHAR(255) NOT NULL,
    audio_format VARCHAR(20) NOT NULL DEFAULT 'audio/webm',
    biometric_features TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 9. Create Voice Verifications Table (Live Approval Verification Audits)
CREATE TABLE voice_verifications (
    id BIGSERIAL PRIMARY KEY,
    leave_id BIGINT NOT NULL REFERENCES leave_requests(id) ON DELETE CASCADE,
    parent_id BIGINT NOT NULL REFERENCES parents(id) ON DELETE CASCADE,
    spoken_transcript TEXT NOT NULL,
    match_score NUMERIC(5,2) NOT NULL,
    is_verified BOOLEAN NOT NULL DEFAULT FALSE,
    audio_path VARCHAR(255),
    verification_timestamp TIMESTAMPTZ DEFAULT NOW()
);

-- 10. Create Notifications Table
CREATE TABLE notifications (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    leave_id BIGINT REFERENCES leave_requests(id) ON DELETE CASCADE,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- ==============================================================================
-- 11. Initial Seed Data (All passwords: 'password123')
-- Hash: $2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.
-- ==============================================================================

-- Seed Users
INSERT INTO users (id, username, email, phone, password_hash, role) VALUES
(1, '21CS101', 'rahul@campusflow.edu', '9876543201', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'student'),
(2, '21CS102', 'priya@campusflow.edu', '9876543202', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'student'),
(3, '9876543210', 'suresh@parent.com', '9876543210', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'parent'),
(4, '9876543220', 'ramesh@parent.com', '9876543220', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'parent'),
(5, 'FAC-CS-01', 'advisor.cs@campusflow.edu', '9876543230', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'advisor'),
(6, 'HOD-CSE-01', 'hod.cse@campusflow.edu', '9876543240', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'hod'),
(7, 'WARDEN-BH-01', 'warden.bh@campusflow.edu', '9876543250', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'warden'),
(8, '25ITR180', 'yazhini@campusflow.edu', '9003497761', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'student'),
(9, '9003497761', 'saranya@parent.com', '9003497761', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'parent');

-- Reset users sequence
SELECT setval('users_id_seq', (SELECT MAX(id) FROM users));

-- Seed Parents
INSERT INTO parents (id, user_id, full_name, phone_number, student_reg_no, preferred_language) VALUES
(1, 3, 'Suresh Sharma', '9876543210', '21CS101', 'ta'),
(2, 4, 'Ramesh Patel', '9876543220', '21CS102', 'hi'),
(3, 9, 'Saranya', '9003497761', '25ITR180', 'ta');

SELECT setval('parents_id_seq', (SELECT MAX(id) FROM parents));

-- Seed Students
INSERT INTO students (id, user_id, register_number, full_name, department, year, section, hostel_status, room_number, parent_id) VALUES
(1, 1, '21CS101', 'Rahul Sharma', 'Computer Science and Engineering', 3, 'A', 'hosteller', 'BH-304', 1),
(2, 2, '21CS102', 'Priya Patel', 'Computer Science and Engineering', 3, 'A', 'day_scholar', NULL, 2),
(3, 8, '25ITR180', 'Yazhini S', 'Information Technology', 2, 'C', 'day_scholar', NULL, 3);

SELECT setval('students_id_seq', (SELECT MAX(id) FROM students));

-- Seed Faculty
INSERT INTO faculty (id, user_id, faculty_id, full_name, department, designation, assigned_year, assigned_section, hostel_block) VALUES
(1, 5, 'FAC-CS-01', 'Dr. A. Ramanathan', 'Computer Science and Engineering', 'Class Advisor - CSE 3A', 3, 'A', NULL),
(2, 6, 'HOD-CSE-01', 'Dr. K. Meenakshi', 'Computer Science and Engineering', 'Head of Department', NULL, NULL, NULL),
(3, 7, 'WARDEN-BH-01', 'Col. R. Balaji', 'Campus Administration', 'Chief Hostel Warden', NULL, NULL, 'Kaveri Boys Hostel');

SELECT setval('faculty_id_seq', (SELECT MAX(id) FROM faculty));

-- Seed Leave Requests
INSERT INTO leave_requests (id, student_id, leave_type, from_date, to_date, from_time, to_time, reason, destination_address, emergency_contact, status, current_stage) VALUES
(1, 1, 'Casual / Home Visit', CURRENT_DATE + INTERVAL '1 day', CURRENT_DATE + INTERVAL '3 days', '08:00:00', '18:00:00', 'Visiting family for annual festival', '42, Temple Street, Madurai, Tamil Nadu - 625001', '9876543210', 'Waiting for Parent', 'parent'),
(2, 1, 'Hostel Outpass', CURRENT_DATE + INTERVAL '5 days', CURRENT_DATE + INTERVAL '6 days', '09:00:00', '19:00:00', 'Attending regional hackathon finals', 'Coders Hub, Guindy, Chennai - 600025', '9876543210', 'Completed', 'completed'),
(3, 2, 'Medical Leave', CURRENT_DATE + INTERVAL '2 days', CURRENT_DATE + INTERVAL '4 days', '08:30:00', '17:00:00', 'Medical checkup and recovery', '15, Anna Nagar, Chennai - 600040', '9876543220', 'Completed', 'completed'),
(4, 3, 'Family Function Leave', CURRENT_DATE + INTERVAL '1 day', CURRENT_DATE + INTERVAL '3 days', '08:30:00', '18:00:00', 'Attending family temple festival in native town', '45, Main Road, Madurai', '9003497761', 'Waiting for Parent', 'parent');

SELECT setval('leave_requests_id_seq', (SELECT MAX(id) FROM leave_requests));

-- Seed Approvals
INSERT INTO approvals (leave_id, approver_role, approver_user_id, action, remarks) VALUES
(2, 'parent', 3, 'approved', 'SMS OTP Authorization Verified (+91 9876543210)'),
(2, 'advisor', 5, 'approved', 'Academic attendance verified (>85%)'),
(2, 'hod', 6, 'approved', 'Department leave sanctioned. Forwarded to Warden.'),
(2, 'warden', 7, 'approved', 'Hostel Gate Pass issued. Safe travels.');

-- Disable Row Level Security to allow direct API inserts & reads
ALTER TABLE users DISABLE ROW LEVEL SECURITY;
ALTER TABLE students DISABLE ROW LEVEL SECURITY;
ALTER TABLE parents DISABLE ROW LEVEL SECURITY;
ALTER TABLE faculty DISABLE ROW LEVEL SECURITY;
ALTER TABLE leave_requests DISABLE ROW LEVEL SECURITY;
ALTER TABLE approvals DISABLE ROW LEVEL SECURITY;
ALTER TABLE voice_samples DISABLE ROW LEVEL SECURITY;
ALTER TABLE voice_verifications DISABLE ROW LEVEL SECURITY;
ALTER TABLE notifications DISABLE ROW LEVEL SECURITY;
