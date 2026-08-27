-- ==============================================================================
-- CampusFlow: Digital Leave Approval Portal
-- Clean Supabase (PostgreSQL) Database Schema (EMPTY TABLES - ZERO SEED DATA)
-- ==============================================================================

-- 1. Clean Up Existing Tables (Drop if exists)
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

-- 8. Create Voice Samples Table (Parent Audio Registration)
CREATE TABLE voice_samples (
    id BIGSERIAL PRIMARY KEY,
    parent_id BIGINT NOT NULL REFERENCES parents(id) ON DELETE CASCADE,
    audio_sample_path VARCHAR(255) NOT NULL,
    audio_format VARCHAR(20) NOT NULL DEFAULT 'audio/webm',
    biometric_features TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 9. Create Voice Verifications Table (Approval Verification Audits)
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
-- 11. Row Level Security & Full Public API Grants
-- (Ensures zero RLS violations for direct client Signup, Login, Leaves & Approvals)
-- ==============================================================================

-- Enable RLS and add universal full-access policies to all tables
DO $$
DECLARE
    tbl text;
BEGIN
    FOR tbl IN 
        SELECT table_name FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_type = 'BASE TABLE'
    LOOP
        EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY;', tbl);
        EXECUTE format('DROP POLICY IF EXISTS "allow_all_%I" ON public.%I;', tbl, tbl);
        EXECUTE format('CREATE POLICY "allow_all_%I" ON public.%I FOR ALL TO public USING (true) WITH CHECK (true);', tbl, tbl);
    END LOOP;
END $$;

-- Grant all privileges to anon, authenticated, and service_role
GRANT USAGE ON SCHEMA public TO anon, authenticated, service_role;
GRANT ALL ON ALL TABLES IN SCHEMA public TO anon, authenticated, service_role;
GRANT ALL ON ALL SEQUENCES IN SCHEMA public TO anon, authenticated, service_role;
GRANT ALL ON ALL ROUTINES IN SCHEMA public TO anon, authenticated, service_role;

ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO anon, authenticated, service_role;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO anon, authenticated, service_role;

