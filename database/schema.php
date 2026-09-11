<?php
/**
 * AnatomIQ – Database Schema & Seed Data (MySQL)
 * Interactive Human Anatomy Learning System
 * Science Department, Lubang National High School
 *
 * HOW TO USE:
 *   Option A: Run via CLI → php database/schema.php
 *   Option B: Open phpMyAdmin → SQL tab → paste the printed SQL
 *   Option C: Access via browser at http://localhost/Prototype2/database/schema.php
 *             (Apache must be running)
 *
 * This script generates and optionally executes the full schema + seed data.
 */

// ── Generate real bcrypt hashes at runtime ─────────────────────────────────
//    These are created fresh each time so they are always valid.
$hashAdmin    = password_hash('admin123',   PASSWORD_BCRYPT, ['cost' => 12]);
$hashTeacher  = password_hash('teacher123', PASSWORD_BCRYPT, ['cost' => 12]);
$hashStudent1 = password_hash('student123', PASSWORD_BCRYPT, ['cost' => 12]);
$hashStudent2 = password_hash('student123', PASSWORD_BCRYPT, ['cost' => 12]);
$hashStudent3 = password_hash('student123', PASSWORD_BCRYPT, ['cost' => 12]);

$SQL_SCHEMA = <<<SQL

-- ================================================================
-- AnatomIQ Database Schema
-- Version: 1.1.0 | Date: 2025
-- ================================================================

CREATE DATABASE IF NOT EXISTS anatomiq_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE anatomiq_db;

-- ── 1. USERS TABLE ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  user_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(150) UNIQUE,
  profile_pic   VARCHAR(255) DEFAULT NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login    TIMESTAMP    DEFAULT NULL,
  INDEX idx_role (role),
  INDEX idx_username (username)
) ENGINE=InnoDB;

-- ── 2. STUDENT PROFILES ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS student_profiles (
  profile_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL UNIQUE,
  student_id    VARCHAR(20)  NOT NULL UNIQUE COMMENT 'School-assigned ID e.g. S-2025-001',
  section       VARCHAR(60)  NOT NULL COMMENT 'e.g. Grade 10 – Narra',
  grade_level   VARCHAR(20)  NOT NULL DEFAULT 'Grade 10',
  birth_date    DATE         DEFAULT NULL,
  guardian_name VARCHAR(120) DEFAULT NULL,
  contact_no    VARCHAR(20)  DEFAULT NULL,
  address       TEXT         DEFAULT NULL,
  school_year   VARCHAR(20)  NOT NULL DEFAULT '2024-2025',
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 3. TEACHER PROFILES ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS teacher_profiles (
  profile_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL UNIQUE,
  teacher_id    VARCHAR(20)  NOT NULL UNIQUE COMMENT 'e.g. T-001',
  subject       VARCHAR(100) NOT NULL DEFAULT 'Science',
  department    VARCHAR(100) DEFAULT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 4. BODY SYSTEMS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS body_systems (
  system_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  system_code   VARCHAR(30)  NOT NULL UNIQUE,
  system_name   VARCHAR(100) NOT NULL,
  icon_emoji    VARCHAR(10)  DEFAULT NULL,
  color_hex     VARCHAR(10)  DEFAULT '#3b82f6',
  description   TEXT         DEFAULT NULL,
  sort_order    INT          NOT NULL DEFAULT 0,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ── 5. MODULES ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS modules (
  module_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  system_id     INT UNSIGNED NOT NULL,
  teacher_id    INT UNSIGNED NOT NULL COMMENT 'Creator user_id',
  title         VARCHAR(200) NOT NULL,
  description   TEXT         DEFAULT NULL,
  thumbnail_url VARCHAR(255) DEFAULT NULL,
  status        ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  sort_order    INT          NOT NULL DEFAULT 0,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  published_at  TIMESTAMP    DEFAULT NULL,
  FOREIGN KEY (system_id)  REFERENCES body_systems(system_id) ON DELETE RESTRICT,
  FOREIGN KEY (teacher_id) REFERENCES users(user_id)          ON DELETE RESTRICT,
  UNIQUE KEY uq_system_title (system_id, title),
  INDEX idx_system (system_id),
  INDEX idx_status (status)
) ENGINE=InnoDB;

-- ── 6. LESSONS ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS lessons (
  lesson_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  module_id     INT UNSIGNED NOT NULL,
  teacher_id    INT UNSIGNED NOT NULL,
  title         VARCHAR(200) NOT NULL,
  lesson_type   ENUM('reading','video','3d_model','interactive','quiz_prep') NOT NULL DEFAULT 'reading',
  content_text  LONGTEXT     DEFAULT NULL COMMENT 'HTML or markdown content',
  media_url     VARCHAR(255) DEFAULT NULL,
  duration_mins INT          DEFAULT NULL,
  sort_order    INT          NOT NULL DEFAULT 0,
  status        ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (module_id)  REFERENCES modules(module_id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES users(user_id)     ON DELETE RESTRICT,
  UNIQUE KEY uq_module_title (module_id, title),
  INDEX idx_module (module_id),
  INDEX idx_type   (lesson_type)
) ENGINE=InnoDB;

-- ── 7. MEDIA LIBRARY ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS media_files (
  media_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uploader_id   INT UNSIGNED NOT NULL,
  system_id     INT UNSIGNED DEFAULT NULL,
  file_name     VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  file_type     ENUM('image','video','model_3d','pdf','audio') NOT NULL,
  file_size_kb  INT UNSIGNED NOT NULL,
  file_path     VARCHAR(500) NOT NULL,
  thumbnail_url VARCHAR(255) DEFAULT NULL,
  description   TEXT         DEFAULT NULL,
  tags          VARCHAR(500) DEFAULT NULL,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (uploader_id) REFERENCES users(user_id)          ON DELETE RESTRICT,
  FOREIGN KEY (system_id)   REFERENCES body_systems(system_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── 8. ASSESSMENTS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS assessments (
  assessment_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id      INT UNSIGNED NOT NULL,
  system_id       INT UNSIGNED DEFAULT NULL,
  module_id       INT UNSIGNED DEFAULT NULL,
  title           VARCHAR(200) NOT NULL,
  assessment_type ENUM('quiz','identification','diagram','exploration','interactive','mixed') NOT NULL DEFAULT 'quiz',
  instructions    TEXT         DEFAULT NULL,
  time_limit_mins INT          DEFAULT NULL,
  passing_score   DECIMAL(5,2) NOT NULL DEFAULT 75.00,
  max_attempts    INT          NOT NULL DEFAULT 3,
  status          ENUM('draft','scheduled','active','closed') NOT NULL DEFAULT 'draft',
  due_date        TIMESTAMP    DEFAULT NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (teacher_id) REFERENCES users(user_id)          ON DELETE RESTRICT,
  FOREIGN KEY (system_id)  REFERENCES body_systems(system_id) ON DELETE SET NULL,
  FOREIGN KEY (module_id)  REFERENCES modules(module_id)      ON DELETE SET NULL,
  INDEX idx_status  (status),
  INDEX idx_teacher (teacher_id)
) ENGINE=InnoDB;

-- ── 9. QUESTIONS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS questions (
  question_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  assessment_id   INT UNSIGNED NOT NULL,
  question_text   TEXT         NOT NULL,
  question_type   ENUM('multiple_choice','true_false','identification','fill_blank','drag_drop','hotspot') NOT NULL DEFAULT 'multiple_choice',
  image_url       VARCHAR(255) DEFAULT NULL COMMENT 'Image for hotspot/diagram questions',
  hotspot_data    JSON         DEFAULT NULL COMMENT 'Clickable areas for hotspot type: [{id, x, y, w, h, label}]',
  points          DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  sort_order      INT          NOT NULL DEFAULT 0,
  FOREIGN KEY (assessment_id) REFERENCES assessments(assessment_id) ON DELETE CASCADE,
  INDEX idx_assessment (assessment_id)
) ENGINE=InnoDB;

-- ── 10. ANSWER OPTIONS ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS answer_options (
  option_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question_id     INT UNSIGNED NOT NULL,
  option_text     VARCHAR(500) NOT NULL,
  is_correct      TINYINT(1)   NOT NULL DEFAULT 0,
  sort_order      INT          NOT NULL DEFAULT 0,
  FOREIGN KEY (question_id) REFERENCES questions(question_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 11. STUDENT PROGRESS (Lesson Completions) ───────────────────
CREATE TABLE IF NOT EXISTS student_lesson_progress (
  progress_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id      INT UNSIGNED NOT NULL,
  lesson_id       INT UNSIGNED NOT NULL,
  status          ENUM('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
  completion_pct  DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  time_spent_secs INT UNSIGNED NOT NULL DEFAULT 0,
  started_at      TIMESTAMP    DEFAULT NULL,
  completed_at    TIMESTAMP    DEFAULT NULL,
  UNIQUE KEY uq_student_lesson (student_id, lesson_id),
  FOREIGN KEY (student_id) REFERENCES users(user_id)    ON DELETE CASCADE,
  FOREIGN KEY (lesson_id)  REFERENCES lessons(lesson_id) ON DELETE CASCADE,
  INDEX idx_student (student_id),
  INDEX idx_lesson  (lesson_id)
) ENGINE=InnoDB;

-- ── 12. ASSESSMENT SUBMISSIONS ──────────────────────────────────
CREATE TABLE IF NOT EXISTS assessment_submissions (
  submission_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id      INT UNSIGNED NOT NULL,
  assessment_id   INT UNSIGNED NOT NULL,
  attempt_number  INT UNSIGNED NOT NULL DEFAULT 1,
  score           DECIMAL(5,2) DEFAULT NULL,
  raw_score       DECIMAL(7,2) DEFAULT NULL,
  max_score       DECIMAL(7,2) DEFAULT NULL,
  time_taken_secs INT UNSIGNED DEFAULT NULL,
  status          ENUM('in_progress','submitted','graded','returned') NOT NULL DEFAULT 'in_progress',
  teacher_feedback TEXT        DEFAULT NULL,
  started_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  submitted_at    TIMESTAMP    DEFAULT NULL,
  graded_at       TIMESTAMP    DEFAULT NULL,
  FOREIGN KEY (student_id)    REFERENCES users(user_id)              ON DELETE CASCADE,
  FOREIGN KEY (assessment_id) REFERENCES assessments(assessment_id) ON DELETE CASCADE,
  INDEX idx_student    (student_id),
  INDEX idx_assessment (assessment_id),
  INDEX idx_status     (status)
) ENGINE=InnoDB;

-- ── 13. ANSWER RESPONSES ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS answer_responses (
  response_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  submission_id   INT UNSIGNED NOT NULL,
  question_id     INT UNSIGNED NOT NULL,
  selected_option INT UNSIGNED DEFAULT NULL COMMENT 'option_id if MC',
  response_text   TEXT         DEFAULT NULL COMMENT 'For identification / fill-in',
  hotspot_clicked JSON         DEFAULT NULL COMMENT 'Clicked area id for hotspot questions',
  is_correct      TINYINT(1)   DEFAULT NULL,
  points_earned   DECIMAL(5,2) DEFAULT 0.00,
  FOREIGN KEY (submission_id) REFERENCES assessment_submissions(submission_id) ON DELETE CASCADE,
  FOREIGN KEY (question_id)   REFERENCES questions(question_id)                ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 14. ANNOUNCEMENTS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS announcements (
  announcement_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_id       INT UNSIGNED NOT NULL,
  title            VARCHAR(255) NOT NULL,
  body             TEXT         NOT NULL,
  category         ENUM('general','lesson','quiz','urgent','event') NOT NULL DEFAULT 'general',
  audience         ENUM('all','section_narra','section_molave','section_dao') NOT NULL DEFAULT 'all',
  is_pinned        TINYINT(1)   NOT NULL DEFAULT 0,
  is_published     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (teacher_id) REFERENCES users(user_id) ON DELETE RESTRICT,
  UNIQUE KEY uq_teacher_title (teacher_id, title),
  INDEX idx_pinned    (is_pinned),
  INDEX idx_published (is_published)
) ENGINE=InnoDB;

-- ── 15. ANNOUNCEMENT READ RECEIPTS ──────────────────────────────
CREATE TABLE IF NOT EXISTS announcement_reads (
  read_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  announcement_id INT UNSIGNED NOT NULL,
  user_id         INT UNSIGNED NOT NULL,
  read_at         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ann_user (announcement_id, user_id),
  FOREIGN KEY (announcement_id) REFERENCES announcements(announcement_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)         REFERENCES users(user_id)                 ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 16. 3D VIEWER EXPLORATION LOG ───────────────────────────────
CREATE TABLE IF NOT EXISTS system_exploration_log (
  log_id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id      INT UNSIGNED NOT NULL,
  system_id       INT UNSIGNED NOT NULL,
  session_start   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  session_end     TIMESTAMP    DEFAULT NULL,
  duration_secs   INT UNSIGNED DEFAULT NULL,
  interactions    INT UNSIGNED NOT NULL DEFAULT 0,
  structures_viewed JSON       DEFAULT NULL,
  FOREIGN KEY (student_id) REFERENCES users(user_id)          ON DELETE CASCADE,
  FOREIGN KEY (system_id)  REFERENCES body_systems(system_id) ON DELETE CASCADE,
  INDEX idx_student (student_id),
  INDEX idx_system  (system_id)
) ENGINE=InnoDB;

-- ── 17. STUDENT PROGRESS SUMMARY (Cached) ───────────────────────
CREATE TABLE IF NOT EXISTS student_progress_summary (
  summary_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id        INT UNSIGNED NOT NULL UNIQUE,
  total_lessons     INT UNSIGNED NOT NULL DEFAULT 0,
  completed_lessons INT UNSIGNED NOT NULL DEFAULT 0,
  avg_quiz_score    DECIMAL(5,2) DEFAULT NULL,
  quizzes_taken     INT UNSIGNED NOT NULL DEFAULT 0,
  quizzes_passed    INT UNSIGNED NOT NULL DEFAULT 0,
  systems_explored  INT UNSIGNED NOT NULL DEFAULT 0,
  total_time_hours  DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  class_rank        INT UNSIGNED DEFAULT NULL,
  last_updated      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── 18. PASSWORD RESET TOKENS ────────────────────────────────────
CREATE TABLE IF NOT EXISTS password_reset_tokens (
  token_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  token_hash  VARCHAR(64)  NOT NULL UNIQUE COMMENT 'SHA-256 hash of the raw token',
  expires_at  TIMESTAMP    NOT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_token (token_hash),
  INDEX idx_user  (user_id)
) ENGINE=InnoDB;

SQL;

// ── Append seed data with real bcrypt hashes ───────────────────────────────
$SQL_SEED = <<<SQL

-- ================================================================
-- SEED DATA
-- ================================================================

-- Body Systems
INSERT IGNORE INTO body_systems (system_code, system_name, icon_emoji, color_hex, description, sort_order) VALUES
  ('skeletal',     'Skeletal System',      '🦴', '#f59e0b', 'The structural framework of the body, providing shape, support, and protection for vital organs.',  1),
  ('muscular',     'Muscular System',      '💪', '#ef4444', 'Over 600 muscles responsible for movement, posture maintenance, and heat production.',               2),
  ('circulatory',  'Circulatory System',   '🫀', '#dc2626', 'Transports blood, oxygen, nutrients, hormones, and waste products throughout the body.',             3),
  ('respiratory',  'Respiratory System',   '🫁', '#3b82f6', 'Enables gas exchange by taking in oxygen and expelling carbon dioxide through the lungs.',            4),
  ('digestive',    'Digestive System',     '🥗', '#10b981', 'Breaks down food into nutrients absorbed by the body, spanning about 9 meters from mouth to anus.',  5),
  ('urinary',      'Urinary System',       '🫘', '#f59e0b', 'Filters blood, removes metabolic waste, regulates fluid balance and produces urine.',                6),
  ('nervous',      'Nervous System',       '🧠', '#8b5cf6', 'Coordinates body activities by transmitting signals between body parts via neurons.',                 7),
  ('reproductive', 'Reproductive System',  '🌸', '#ec4899', 'Responsible for sexual reproduction and production of sex hormones.',                                8),
  ('endocrine',    'Endocrine System',     '⚗️', '#14b8a6', 'Regulates body functions through hormones secreted by glands into the bloodstream.',                9);

SQL;

// Build user inserts with real hashes
$SQL_SEED .= "-- Demo Users (passwords are real bcrypt hashes)\n";
$SQL_SEED .= "INSERT INTO users (username, password_hash, role, full_name, email) VALUES\n";
$SQL_SEED .= "  ('admin',      '{$hashAdmin}',    'teacher', 'Mrs. Cristina Reyes',  'c.reyes@lubangscience.edu.ph'),\n";
$SQL_SEED .= "  ('teacher',    '{$hashTeacher}',  'teacher', 'Mr. Rolando Bautista', 'r.bautista@lubangscience.edu.ph'),\n";
$SQL_SEED .= "  ('student001', '{$hashStudent1}', 'student', 'Maria Santos',         'm.santos@student.lubang.edu.ph'),\n";
$SQL_SEED .= "  ('student002', '{$hashStudent2}', 'student', 'Juan dela Cruz',       'j.delacruz@student.lubang.edu.ph'),\n";
$SQL_SEED .= "  ('student003', '{$hashStudent3}', 'student', 'Ana Reyes',            'a.reyes@student.lubang.edu.ph')\n";
$SQL_SEED .= "ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role), full_name = VALUES(full_name), email = VALUES(email);\n\n";

$SQL_SEED .= <<<SQL
-- Teacher Profiles
INSERT IGNORE INTO teacher_profiles (user_id, teacher_id, subject, department)
SELECT user_id, 'T-001', 'Science', 'Science Department' FROM users WHERE username = 'admin'
UNION ALL
SELECT user_id, 'T-002', 'Biology', 'Science Department' FROM users WHERE username = 'teacher';

-- Student Profiles
INSERT IGNORE INTO student_profiles (user_id, student_id, section, grade_level, school_year)
SELECT user_id, 'S-2025-001', 'Grade 10 – Narra',   'Grade 10', '2024-2025' FROM users WHERE username = 'student001'
UNION ALL
SELECT user_id, 'S-2025-002', 'Grade 10 – Molave',  'Grade 10', '2024-2025' FROM users WHERE username = 'student002'
UNION ALL
SELECT user_id, 'S-2025-003', 'Grade 10 – Narra',   'Grade 10', '2024-2025' FROM users WHERE username = 'student003';

-- ── MODULES ──────────────────────────────────────────────────────
INSERT IGNORE INTO modules (system_id, teacher_id, title, description, status, sort_order, published_at)
SELECT
  bs.system_id,
  u.user_id,
  CONCAT('Introduction to the ', bs.system_name),
  CONCAT('Foundational concepts of the ', bs.system_name, ': key structures, functions, and their role in the body.'),
  'published',
  1,
  NOW()
FROM body_systems bs
CROSS JOIN users u
WHERE u.username = 'admin';

INSERT IGNORE INTO modules (system_id, teacher_id, title, description, status, sort_order, published_at)
SELECT
  bs.system_id,
  u.user_id,
  CONCAT(bs.system_name, ': Key Structures & Functions'),
  CONCAT('In-depth exploration of the main structures, organs, and cells that make up the ', bs.system_name, '.'),
  'published',
  2,
  NOW()
FROM body_systems bs
CROSS JOIN users u
WHERE u.username = 'admin';

-- ── LESSONS (2 per module: Reading + Quiz Prep) ─────────────────
INSERT IGNORE INTO lessons (module_id, teacher_id, title, lesson_type, content_text, duration_mins, sort_order, status)
SELECT
  m.module_id,
  u.user_id,
  CONCAT('Reading: ', m.title),
  'reading',
  CONCAT('<h2>', m.title, '</h2><p>This lesson covers the fundamental concepts of this body system. Study the key structures, their names, locations, and primary functions.</p><p>The human body is made up of multiple organ systems that work together to maintain homeostasis. Understanding each system is essential for understanding how the body functions as a whole.</p>'),
  15,
  1,
  'published'
FROM modules m
CROSS JOIN users u
WHERE u.username = 'admin';

INSERT IGNORE INTO lessons (module_id, teacher_id, title, lesson_type, content_text, duration_mins, sort_order, status)
SELECT
  m.module_id,
  u.user_id,
  CONCAT('Quiz Preparation: ', m.title),
  'quiz_prep',
  '<p>Review the key terms and concepts before taking the quiz. Make sure you can identify all major structures and explain their functions.</p>',
  10,
  2,
  'published'
FROM modules m
CROSS JOIN users u
WHERE u.username = 'admin';

-- ── ASSESSMENTS ──────────────────────────────────────────────────
-- Skeletal System Quiz (active)
INSERT IGNORE INTO assessments (teacher_id, system_id, title, assessment_type, instructions, time_limit_mins, passing_score, max_attempts, status, due_date)
SELECT u.user_id, bs.system_id,
  'Skeletal System Chapter Quiz',
  'quiz',
  'Answer all 10 questions. You have 30 minutes. Choose the best answer for each question.',
  30, 75.00, 3, 'active', DATE_ADD(NOW(), INTERVAL 7 DAY)
FROM users u, body_systems bs
WHERE u.username = 'admin' AND bs.system_code = 'skeletal';

-- Nervous System Quiz (active)
INSERT IGNORE INTO assessments (teacher_id, system_id, title, assessment_type, instructions, time_limit_mins, passing_score, max_attempts, status, due_date)
SELECT u.user_id, bs.system_id,
  'Nervous System Chapter Quiz',
  'quiz',
  'This quiz covers the brain, spinal cord, neurons, and the peripheral nervous system. You have 30 minutes.',
  30, 75.00, 3, 'active', DATE_ADD(NOW(), INTERVAL 3 DAY)
FROM users u, body_systems bs
WHERE u.username = 'admin' AND bs.system_code = 'nervous';

-- Circulatory System Quiz (active)
INSERT IGNORE INTO assessments (teacher_id, system_id, title, assessment_type, instructions, time_limit_mins, passing_score, max_attempts, status, due_date)
SELECT u.user_id, bs.system_id,
  'Circulatory System Quiz',
  'quiz',
  'Answer questions about the heart, blood vessels, and blood composition.',
  30, 75.00, 3, 'active', DATE_ADD(NOW(), INTERVAL 10 DAY)
FROM users u, body_systems bs
WHERE u.username = 'admin' AND bs.system_code = 'circulatory';

-- Label the Heart – Interactive Hotspot (active)
INSERT IGNORE INTO assessments (teacher_id, system_id, title, assessment_type, instructions, time_limit_mins, passing_score, max_attempts, status, due_date)
SELECT u.user_id, bs.system_id,
  'Label the Heart – Hotspot Activity',
  'interactive',
  'Click on the correct part of the heart diagram when prompted. Identify all labeled structures.',
  20, 75.00, 3, 'active', DATE_ADD(NOW(), INTERVAL 5 DAY)
FROM users u, body_systems bs
WHERE u.username = 'admin' AND bs.system_code = 'circulatory';

-- Digestive System Identification (active)
INSERT IGNORE INTO assessments (teacher_id, system_id, title, assessment_type, instructions, time_limit_mins, passing_score, max_attempts, status, due_date)
SELECT u.user_id, bs.system_id,
  'Digestive System Identification',
  'identification',
  'Type the correct name of each organ or structure shown.',
  25, 75.00, 3, 'active', DATE_ADD(NOW(), INTERVAL 14 DAY)
FROM users u, body_systems bs
WHERE u.username = 'admin' AND bs.system_code = 'digestive';

-- ── QUESTIONS – Skeletal System Quiz ─────────────────────────────
-- Assessment ID will be 1 (first inserted above)
SET @skel_id = (SELECT assessment_id FROM assessments WHERE title = 'Skeletal System Chapter Quiz' LIMIT 1);

INSERT IGNORE INTO questions (assessment_id, question_text, question_type, points, sort_order) VALUES
  (@skel_id, 'How many bones does the adult human skeleton have?', 'multiple_choice', 1.00, 1),
  (@skel_id, 'Which is the longest and strongest bone in the human body?', 'multiple_choice', 1.00, 2),
  (@skel_id, 'What is the primary mineral that makes bones hard?', 'multiple_choice', 1.00, 3),
  (@skel_id, 'The ribcage is made up of how many pairs of ribs?', 'multiple_choice', 1.00, 4),
  (@skel_id, 'Which type of bone cell is responsible for breaking down bone tissue?', 'multiple_choice', 1.00, 5),
  (@skel_id, 'What is the smallest bone in the human body?', 'multiple_choice', 1.00, 6),
  (@skel_id, 'Which part of the skeleton protects the brain?', 'multiple_choice', 1.00, 7),
  (@skel_id, 'What type of joint allows rotation, such as the neck joint?', 'multiple_choice', 1.00, 8),
  (@skel_id, 'The spine is made up of individual bones called:', 'multiple_choice', 1.00, 9),
  (@skel_id, 'Which of the following is a function of the skeletal system?', 'multiple_choice', 1.00, 10);

-- Answer Options – Skeletal Quiz
-- Q1: 206 bones
SET @q1 = (SELECT question_id FROM questions WHERE assessment_id = @skel_id AND sort_order = 1 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@q1, '196', 0, 1), (@q1, '206', 1, 2), (@q1, '216', 0, 3), (@q1, '226', 0, 4);

-- Q2: Femur
SET @q2 = (SELECT question_id FROM questions WHERE assessment_id = @skel_id AND sort_order = 2 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@q2, 'Tibia', 0, 1), (@q2, 'Humerus', 0, 2), (@q2, 'Femur', 1, 3), (@q2, 'Fibula', 0, 4);

-- Q3: Calcium & Phosphorus
SET @q3 = (SELECT question_id FROM questions WHERE assessment_id = @skel_id AND sort_order = 3 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@q3, 'Iron', 0, 1), (@q3, 'Sodium', 0, 2), (@q3, 'Magnesium', 0, 3), (@q3, 'Calcium', 1, 4);

-- Q4: 12 pairs
SET @q4 = (SELECT question_id FROM questions WHERE assessment_id = @skel_id AND sort_order = 4 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@q4, '10 pairs', 0, 1), (@q4, '12 pairs', 1, 2), (@q4, '14 pairs', 0, 3), (@q4, '8 pairs', 0, 4);

-- Q5: Osteoclasts
SET @q5 = (SELECT question_id FROM questions WHERE assessment_id = @skel_id AND sort_order = 5 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@q5, 'Osteoblasts', 0, 1), (@q5, 'Osteocytes', 0, 2), (@q5, 'Osteoclasts', 1, 3), (@q5, 'Chondrocytes', 0, 4);

-- Q6: Stapes
SET @q6 = (SELECT question_id FROM questions WHERE assessment_id = @skel_id AND sort_order = 6 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@q6, 'Malleus', 0, 1), (@q6, 'Incus', 0, 2), (@q6, 'Stapes', 1, 3), (@q6, 'Hyoid', 0, 4);

-- Q7: Skull
SET @q7 = (SELECT question_id FROM questions WHERE assessment_id = @skel_id AND sort_order = 7 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@q7, 'Vertebrae', 0, 1), (@q7, 'Pelvis', 0, 2), (@q7, 'Skull', 1, 3), (@q7, 'Sternum', 0, 4);

-- Q8: Pivot joint
SET @q8 = (SELECT question_id FROM questions WHERE assessment_id = @skel_id AND sort_order = 8 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@q8, 'Ball-and-socket', 0, 1), (@q8, 'Hinge', 0, 2), (@q8, 'Pivot', 1, 3), (@q8, 'Gliding', 0, 4);

-- Q9: Vertebrae
SET @q9 = (SELECT question_id FROM questions WHERE assessment_id = @skel_id AND sort_order = 9 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@q9, 'Discs', 0, 1), (@q9, 'Vertebrae', 1, 2), (@q9, 'Ligaments', 0, 3), (@q9, 'Cartilage', 0, 4);

-- Q10: Blood cell production
SET @q10 = (SELECT question_id FROM questions WHERE assessment_id = @skel_id AND sort_order = 10 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@q10, 'Digestion of food', 0, 1), (@q10, 'Gas exchange', 0, 2), (@q10, 'Production of blood cells', 1, 3), (@q10, 'Hormone secretion', 0, 4);

-- ── QUESTIONS – Nervous System Quiz ──────────────────────────────
SET @nerv_id = (SELECT assessment_id FROM assessments WHERE title = 'Nervous System Chapter Quiz' LIMIT 1);

INSERT IGNORE INTO questions (assessment_id, question_text, question_type, points, sort_order) VALUES
  (@nerv_id, 'What is the largest and most complex part of the brain responsible for higher cognitive functions?', 'multiple_choice', 1.00, 1),
  (@nerv_id, 'Which part of the nervous system is responsible for the "fight or flight" response?', 'multiple_choice', 1.00, 2),
  (@nerv_id, 'Neurons transmit signals through specialized junctions called:', 'multiple_choice', 1.00, 3),
  (@nerv_id, 'The protective covering of neurons that speeds up nerve impulse transmission is called:', 'multiple_choice', 1.00, 4),
  (@nerv_id, 'Which of the following is NOT a function of the nervous system?', 'multiple_choice', 1.00, 5),
  (@nerv_id, 'How many pairs of cranial nerves originate from the brain?', 'multiple_choice', 1.00, 6),
  (@nerv_id, 'The cerebellum is primarily responsible for:', 'multiple_choice', 1.00, 7),
  (@nerv_id, 'What type of neuron carries signals FROM the CNS TO muscles?', 'multiple_choice', 1.00, 8),
  (@nerv_id, 'The space between two neurons where chemical signals cross is the:', 'multiple_choice', 1.00, 9),
  (@nerv_id, 'Which brain structure controls heart rate, breathing, and blood pressure?', 'multiple_choice', 1.00, 10);

-- Nervous System Answer Options
SET @n1 = (SELECT question_id FROM questions WHERE assessment_id = @nerv_id AND sort_order = 1 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@n1, 'Cerebellum', 0, 1), (@n1, 'Brainstem', 0, 2), (@n1, 'Cerebrum', 1, 3), (@n1, 'Hypothalamus', 0, 4);

SET @n2 = (SELECT question_id FROM questions WHERE assessment_id = @nerv_id AND sort_order = 2 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@n2, 'Parasympathetic NS', 0, 1), (@n2, 'Somatic NS', 0, 2), (@n2, 'Central NS', 0, 3), (@n2, 'Sympathetic NS', 1, 4);

SET @n3 = (SELECT question_id FROM questions WHERE assessment_id = @nerv_id AND sort_order = 3 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@n3, 'Dendrites', 0, 1), (@n3, 'Synapses', 1, 2), (@n3, 'Axons', 0, 3), (@n3, 'Myelin sheaths', 0, 4);

SET @n4 = (SELECT question_id FROM questions WHERE assessment_id = @nerv_id AND sort_order = 4 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@n4, 'Schwann cells', 0, 1), (@n4, 'Axon terminals', 0, 2), (@n4, 'Myelin sheath', 1, 3), (@n4, 'Node of Ranvier', 0, 4);

SET @n5 = (SELECT question_id FROM questions WHERE assessment_id = @nerv_id AND sort_order = 5 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@n5, 'Sensory input', 0, 1), (@n5, 'Hormone production', 1, 2), (@n5, 'Integration', 0, 3), (@n5, 'Motor output', 0, 4);

SET @n6 = (SELECT question_id FROM questions WHERE assessment_id = @nerv_id AND sort_order = 6 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@n6, '8', 0, 1), (@n6, '10', 0, 2), (@n6, '12', 1, 3), (@n6, '14', 0, 4);

SET @n7 = (SELECT question_id FROM questions WHERE assessment_id = @nerv_id AND sort_order = 7 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@n7, 'Memory formation', 0, 1), (@n7, 'Vision processing', 0, 2), (@n7, 'Balance and coordination', 1, 3), (@n7, 'Language production', 0, 4);

SET @n8 = (SELECT question_id FROM questions WHERE assessment_id = @nerv_id AND sort_order = 8 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@n8, 'Sensory neurons', 0, 1), (@n8, 'Interneurons', 0, 2), (@n8, 'Motor neurons', 1, 3), (@n8, 'Relay neurons', 0, 4);

SET @n9 = (SELECT question_id FROM questions WHERE assessment_id = @nerv_id AND sort_order = 9 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@n9, 'Axon hillock', 0, 1), (@n9, 'Synaptic cleft', 1, 2), (@n9, 'Node of Ranvier', 0, 3), (@n9, 'Dendrite', 0, 4);

SET @n10 = (SELECT question_id FROM questions WHERE assessment_id = @nerv_id AND sort_order = 10 LIMIT 1);
INSERT IGNORE INTO answer_options (question_id, option_text, is_correct, sort_order) VALUES
  (@n10, 'Cerebellum', 0, 1), (@n10, 'Pons', 0, 2), (@n10, 'Medulla oblongata', 1, 3), (@n10, 'Thalamus', 0, 4);

-- ── ANNOUNCEMENTS ────────────────────────────────────────────────
INSERT IGNORE INTO announcements (teacher_id, title, body, category, audience, is_pinned, is_published)
SELECT u.user_id,
  'Welcome to AnatomIQ – School Year 2024-2025',
  'Welcome to our interactive anatomy learning system! All modules are now available. Start with the Skeletal System and work your way through all 9 body systems. Your progress will be tracked automatically.',
  'general', 'all', 1, 1
FROM users u WHERE u.username = 'admin';

INSERT IGNORE INTO announcements (teacher_id, title, body, category, audience, is_pinned, is_published)
SELECT u.user_id,
  'Nervous System Quiz – Due This Week',
  'The Nervous System Chapter Quiz is now active and due in 3 days. Review your notes on the brain, spinal cord, neurons, and peripheral nervous system. The quiz has 10 questions and a 30-minute time limit.',
  'quiz', 'all', 0, 1
FROM users u WHERE u.username = 'admin';

INSERT IGNORE INTO announcements (teacher_id, title, body, category, audience, is_pinned, is_published)
SELECT u.user_id,
  'Skeletal System Module Now Available',
  'The complete Skeletal System module has been published. It includes reading materials, a 3D explorer activity, and a 10-question quiz. All Grade 10 students are expected to complete it by end of week.',
  'lesson', 'all', 0, 1
FROM users u WHERE u.username = 'admin';

-- ── PROGRESS SUMMARY (initialize dynamically for all students) ──────────────
INSERT IGNORE INTO student_progress_summary (student_id, total_lessons, completed_lessons, avg_quiz_score, quizzes_taken, quizzes_passed, systems_explored, total_time_hours)
SELECT user_id, (SELECT COUNT(*) FROM lessons WHERE status = 'published'), 0, NULL, 0, 0, 0, 0.00 FROM users WHERE role = 'student';

SQL;

$fullSQL = $SQL_SCHEMA . $SQL_SEED;

// ── Output ─────────────────────────────────────────────────────────────────
$isCli     = php_sapi_name() === 'cli';
$isBrowser = !$isCli;

if ($isBrowser) {
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<title>AnatomIQ – Schema Setup</title>';
    echo '<style>body{font-family:monospace;background:#0a0f1e;color:#e2e8f0;padding:2rem;max-width:900px;margin:0 auto}';
    echo 'h1{color:#60a5fa}h2{color:#a78bfa}pre{background:#1a2540;padding:1rem;border-radius:8px;overflow:auto;font-size:0.8rem}';
    echo '.success{color:#34d399}.error{color:#f87171}.info{color:#93c5fd}';
    echo '.btn{display:inline-block;padding:0.5rem 1.5rem;background:#3b82f6;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:1rem;margin:0.5rem 0}';
    echo '</style></head><body>';
    echo '<h1>🧬 AnatomIQ – Database Setup</h1>';
}

// ── Auto-execute if DB credentials are configured ─────────────────────────
$autoRun = true;
try {
    require_once __DIR__ . '/connection.php';
    $pdo = Database::getInstance()->getConnection();

    if ($autoRun) {
        $errors   = [];
        $executed = 0;

        // Split and execute individual statements cleanly
        $rawStatements = preg_split('/;\s*(\r?\n)+/', $fullSQL);
        $statements    = [];
        foreach ($rawStatements as $raw) {
            $lines      = explode("\n", $raw);
            $cleanLines = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (!str_starts_with($trimmed, '--') && !str_starts_with($trimmed, '#')) {
                    $cleanLines[] = $line;
                }
            }
            $cleanStmt = trim(implode("\n", $cleanLines));
            if (!empty($cleanStmt)) {
                $statements[] = $cleanStmt;
            }
        }

        foreach ($statements as $stmt) {
            if (empty(trim($stmt))) continue;
            try {
                $pdo->exec($stmt);
                $executed++;
            } catch (PDOException $e) {
                $errors[] = "Statement error: " . $e->getMessage() . "\nSQL: " . substr($stmt, 0, 100);
            }
        }

        $msg = "✅ Schema executed successfully! {$executed} statements ran.";
        if (!empty($errors)) {
            $msg .= " (" . count($errors) . " warnings/skipped)";
        }
        echo $isBrowser
            ? "<p class='success'>{$msg}</p>"
            : $msg . PHP_EOL;
    }
} catch (Throwable $e) {
    $msg = "⚠️  Database connection unavailable: " . $e->getMessage() . "\n" .
           "    Copy the SQL below and run it manually in phpMyAdmin.";
    echo $isBrowser
        ? "<p class='info'>" . nl2br(htmlspecialchars($msg)) . "</p>"
        : $msg . PHP_EOL;
}

// Always print the SQL for reference
if ($isBrowser) {
    echo '<h2>📋 Generated SQL</h2>';
    echo '<pre>' . htmlspecialchars($fullSQL) . '</pre>';
    echo '</body></html>';
} else {
    echo PHP_EOL . "── Generated SQL ──" . PHP_EOL;
    echo $fullSQL . PHP_EOL;
}
?>
