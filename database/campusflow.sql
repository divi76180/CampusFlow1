-- CampusFlow: Digital Leave Approval Portal Database Schema
-- Compatible with MySQL 5.7+ / 8.x / MariaDB

CREATE DATABASE IF NOT EXISTS `campusflow` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campusflow`;

-- 1. Users Table (Central authentication entity)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `email` VARCHAR(150) NULL UNIQUE,
    `phone` VARCHAR(30) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'parent', 'advisor', 'hod', 'warden') NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Parents Table
CREATE TABLE IF NOT EXISTS `parents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `email` VARCHAR(150) NULL,
    `student_reg_no` VARCHAR(50) NOT NULL,
    `preferred_language` VARCHAR(50) DEFAULT 'ta',
    `voice_enrolled` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_parents_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Students Table
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `register_number` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `year` VARCHAR(10) NOT NULL,
    `section` VARCHAR(10) NOT NULL,
    `hostel_status` ENUM('hosteller', 'day_scholar') NOT NULL DEFAULT 'day_scholar',
    `hostel_block` VARCHAR(100) NULL,
    `room_number` VARCHAR(50) NULL,
    `parent_id` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_students_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Faculty Table (Advisors, HODs, Wardens)
CREATE TABLE IF NOT EXISTS `faculty` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `faculty_id_no` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) NOT NULL,
    `role_type` ENUM('advisor', 'hod', 'warden') NOT NULL,
    `department` VARCHAR(100) NULL,
    `section_handled` VARCHAR(50) NULL,
    `hostel_name` VARCHAR(100) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_faculty_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Leave Requests Table
CREATE TABLE IF NOT EXISTS `leave_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `leave_type` VARCHAR(100) NOT NULL,
    `from_date` DATE NOT NULL,
    `to_date` DATE NOT NULL,
    `from_time` TIME NULL,
    `to_time` TIME NULL,
    `reason` TEXT NOT NULL,
    `destination_address` VARCHAR(255) NOT NULL,
    `emergency_contact` VARCHAR(30) NULL,
    `status` ENUM(
        'Submitted',
        'Waiting for Parent',
        'Parent Approved',
        'Parent Rejected',
        'Waiting for Class Advisor',
        'Advisor Approved',
        'Advisor Rejected',
        'Waiting for HOD',
        'HOD Approved',
        'HOD Rejected',
        'Waiting for Warden',
        'Warden Approved',
        'Warden Rejected',
        'Completed'
    ) NOT NULL DEFAULT 'Waiting for Parent',
    `current_stage` VARCHAR(50) DEFAULT 'parent',
    `parent_verified_at` DATETIME NULL,
    `advisor_verified_at` DATETIME NULL,
    `hod_verified_at` DATETIME NULL,
    `warden_verified_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_leave_student` FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Approvals History / Audit Trail Table
CREATE TABLE IF NOT EXISTS `approvals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `leave_id` INT NOT NULL,
    `approver_user_id` INT NOT NULL,
    `approver_role` ENUM('parent', 'advisor', 'hod', 'warden') NOT NULL,
    `action` ENUM('approved', 'rejected') NOT NULL,
    `remarks` TEXT NULL,
    `action_timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_approvals_leave` FOREIGN KEY (`leave_id`) REFERENCES `leave_requests`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_approvals_user` FOREIGN KEY (`approver_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Voice Samples Table (Parent voice registration)
CREATE TABLE IF NOT EXISTS `voice_samples` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NOT NULL,
    `audio_path` VARCHAR(255) NOT NULL,
    `passphrase_text` VARCHAR(255) NULL,
    `audio_features_json` TEXT NULL,
    `sample_hash` VARCHAR(100) NULL,
    `registered_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_voice_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Voice Verifications Table (Audit for each leave voice approval attempt)
CREATE TABLE IF NOT EXISTS `voice_verifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `leave_id` INT NOT NULL,
    `parent_id` INT NOT NULL,
    `recorded_audio_path` VARCHAR(255) NULL,
    `transcript_detected` TEXT NULL,
    `match_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `verified_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_verif_leave` FOREIGN KEY (`leave_id`) REFERENCES `leave_requests`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_verif_parent` FOREIGN KEY (`parent_id`) REFERENCES `parents`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `action_url` VARCHAR(255) NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- SEED DATA (Testing & Evaluation Accounts)
-- Default Password for all accounts: password123
-- Hash: $2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.
-- ==========================================================

-- 1. Insert Users
INSERT INTO `users` (`id`, `username`, `email`, `phone`, `password_hash`, `role`) VALUES
(1, '21CS101', 'rahul@campusflow.edu', '9876543201', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'student'),
(2, '21CS102', 'priya@campusflow.edu', '9876543202', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'student'),
(3, '9876543210', 'suresh@parent.com', '9876543210', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'parent'),
(4, '9876543220', 'ramesh@parent.com', '9876543220', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'parent'),
(5, 'FAC-CS-01', 'advisor.cs@campusflow.edu', '9876543203', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'advisor'),
(6, 'HOD-CSE-01', 'hod.cse@campusflow.edu', '9876543204', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'hod'),
(7, 'WARDEN-BH-01', 'warden.boys@campusflow.edu', '9876543205', '$2y$10$aJtXH9KqktnGf1mq/3K.R.zxsBFlQB8lg7QmxwnVE3AEYTu6dPVi.', 'warden')
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`);

-- 2. Insert Parents
INSERT INTO `parents` (`id`, `user_id`, `full_name`, `phone`, `email`, `student_reg_no`, `preferred_language`, `voice_enrolled`) VALUES
(1, 3, 'Suresh Sharma', '9876543210', 'suresh@parent.com', '21CS101', 'ta', 1),
(2, 4, 'Ramesh Patel', '9876543220', 'ramesh@parent.com', '21CS102', 'en', 1)
ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`);

-- 3. Insert Students
INSERT INTO `students` (`id`, `user_id`, `register_number`, `full_name`, `email`, `phone`, `department`, `year`, `section`, `hostel_status`, `parent_id`) VALUES
(1, 1, '21CS101', 'Rahul Sharma', 'rahul@campusflow.edu', '9876543201', 'Computer Science and Engineering', '3', 'A', 'hosteller', 1),
(2, 2, '21CS102', 'Priya Patel', 'priya@campusflow.edu', '9876543202', 'Computer Science and Engineering', '3', 'A', 'day_scholar', 2)
ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`);

-- 4. Insert Faculty
INSERT INTO `faculty` (`id`, `user_id`, `faculty_id_no`, `full_name`, `email`, `phone`, `role_type`, `department`, `section_handled`, `hostel_name`) VALUES
(1, 5, 'FAC-CS-01', 'Dr. A. Ramanathan', 'advisor.cs@campusflow.edu', '9876543203', 'advisor', 'Computer Science and Engineering', '3-A', NULL),
(2, 6, 'HOD-CSE-01', 'Dr. K. Meenakshi', 'hod.cse@campusflow.edu', '9876543204', 'hod', 'Computer Science and Engineering', NULL, NULL),
(3, 7, 'WARDEN-BH-01', 'Col. R. Balaji', 'warden.boys@campusflow.edu', '9876543205', 'warden', NULL, NULL, 'Kaveri Boys Hostel')
ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`);

-- 5. Insert Sample Voice Samples for Parents
INSERT INTO `voice_samples` (`id`, `parent_id`, `audio_path`, `passphrase_text`, `audio_features_json`, `sample_hash`) VALUES
(1, 1, 'uploads/voice_samples/sample_parent_1.wav', 'I hereby approve leave for my ward Rahul Sharma', '{"energy": 0.84, "pitch": 142.5, "sample_rate": 44100}', 'hash_sample_parent_1'),
(2, 2, 'uploads/voice_samples/sample_parent_2.wav', 'I hereby approve leave for my ward Priya Patel', '{"energy": 0.81, "pitch": 155.0, "sample_rate": 44100}', 'hash_sample_parent_2')
ON DUPLICATE KEY UPDATE `audio_path`=VALUES(`audio_path`);

-- 6. Insert Initial Demo Leave Request for Rahul (Hosteller)
INSERT INTO `leave_requests` (`id`, `student_id`, `leave_type`, `from_date`, `to_date`, `from_time`, `to_time`, `reason`, `destination_address`, `emergency_contact`, `status`, `current_stage`) VALUES
(1, 1, 'Casual / Home Visit', DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), '08:00:00', '18:00:00', 'Attending cousin sister wedding ceremony in hometown and family gathering.', 'No. 42, Gandhi Street, Madurai, Tamil Nadu - 625001', '9876543210', 'Waiting for Parent', 'parent')
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`);
