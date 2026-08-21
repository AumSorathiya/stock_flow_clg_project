CREATE DATABASE IF NOT EXISTS attendance_db;
USE attendance_db;

-- Master Table: Stores lecture metadata
CREATE TABLE IF NOT EXISTS lectures (
    lecture_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(100) NOT NULL,
    lecture_date DATE NOT NULL,
    faculty_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Detail Table: Stores attendance status per student mapped to the lecture
CREATE TABLE IF NOT EXISTS attendance_details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    lecture_id INT NOT NULL,
    student_roll_no VARCHAR(20) NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    status ENUM('Present', 'Absent') NOT NULL,
    FOREIGN KEY (lecture_id) REFERENCES lectures(lecture_id) ON DELETE CASCADE
);

-- Seed Data: Sample students list for the form
CREATE TABLE IF NOT EXISTS students (
    roll_no VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

INSERT INTO students (roll_no, name) VALUES 
('MCA01', 'Aum Patel'),
('MCA02', 'Bhavin Shah'),
('MCA03', 'Chirag Mehta'),
('MCA04', 'Divya Sharma');