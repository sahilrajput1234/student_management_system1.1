-- Student Management System Database Schema

-- Drop existing tables if they exist
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS grades;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'teacher', 'student') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create students table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    status ENUM('active', 'inactive') DEFAULT 'active',
    enrolled_date DATE DEFAULT CURRENT_DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create courses table
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    credits INT NOT NULL,
    instructor VARCHAR(100),
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    start_date DATE,
    end_date DATE,
    capacity INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create enrollments table
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date DATE DEFAULT CURRENT_DATE,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY (student_id, course_id)
);

-- Create attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY (student_id, course_id, attendance_date)
);

-- Create grades table
CREATE TABLE grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    grade_type ENUM('assignment', 'quiz', 'test', 'exam', 'project') NOT NULL,
    grade_title VARCHAR(100) NOT NULL,
    grade_value DECIMAL(5,2) NOT NULL,
    max_grade DECIMAL(5,2) NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$8SQ6yi5VIY2gc5dly8rwNeRzkk8oPYFU.P/KucYAB.n3d8Ee517n.', 'admin@example.com', 'admin');
-- Default password is 'admin123' (hashed with bcrypt)

-- Insert sample data for testing

-- Sample students
INSERT INTO students (name, email, phone, address, date_of_birth, gender, status) VALUES
('John Doe', 'john.doe@example.com', '123-456-7890', '123 Main St, City, Country', '2000-01-15', 'male', 'active'),
('Jane Smith', 'jane.smith@example.com', '234-567-8901', '456 Elm St, City, Country', '2001-03-20', 'female', 'active'),
('Robert Johnson', 'robert.johnson@example.com', '345-678-9012', '789 Oak St, City, Country', '1999-07-10', 'male', 'active'),
('Emily Brown', 'emily.brown@example.com', '456-789-0123', '101 Pine St, City, Country', '2002-05-05', 'female', 'active'),
('Michael Davis', 'michael.davis@example.com', '567-890-1234', '202 Maple St, City, Country', '2000-11-25', 'male', 'active');

-- Sample courses
INSERT INTO courses (name, code, credits, instructor, description, status, start_date, end_date, capacity) VALUES
('Introduction to Computer Science', 'CS101', 3, 'Dr. Smith', 'A beginner-level course introducing fundamental concepts of computer science.', 'active', '2023-09-01', '2023-12-15', 30),
('Mathematics for Engineers', 'MATH201', 4, 'Prof. Johnson', 'Advanced mathematics concepts applied to engineering problems.', 'active', '2023-09-01', '2023-12-15', 25),
('English Composition', 'ENG101', 3, 'Dr. Williams', 'A course focused on developing writing skills for academic and professional contexts.', 'active', '2023-09-01', '2023-12-15', 35),
('Introduction to Physics', 'PHYS101', 4, 'Prof. Brown', 'Basic principles of physics with laboratory experiments.', 'active', '2023-09-01', '2023-12-15', 28),
('History of Art', 'ART202', 3, 'Dr. Garcia', 'Survey of major art movements throughout history.', 'active', '2023-09-01', '2023-12-15', 40);

-- Sample enrollments
INSERT INTO enrollments (student_id, course_id, enrollment_date, status) VALUES
(1, 1, '2023-08-25', 'active'),
(1, 3, '2023-08-25', 'active'),
(2, 2, '2023-08-26', 'active'),
(2, 4, '2023-08-26', 'active'),
(3, 1, '2023-08-27', 'active'),
(3, 5, '2023-08-27', 'active'),
(4, 3, '2023-08-28', 'active'),
(4, 5, '2023-08-28', 'active'),
(5, 2, '2023-08-29', 'active'),
(5, 4, '2023-08-29', 'active');

-- Sample attendance records
INSERT INTO attendance (student_id, course_id, attendance_date, status) VALUES
(1, 1, '2023-09-05', 'present'),
(1, 3, '2023-09-05', 'present'),
(2, 2, '2023-09-05', 'present'),
(2, 4, '2023-09-05', 'absent'),
(3, 1, '2023-09-05', 'late'),
(3, 5, '2023-09-05', 'present'),
(4, 3, '2023-09-05', 'present'),
(4, 5, '2023-09-05', 'present'),
(5, 2, '2023-09-05', 'excused'),
(5, 4, '2023-09-05', 'present');

-- Sample grades
INSERT INTO grades (student_id, course_id, grade_type, grade_title, grade_value, max_grade) VALUES
(1, 1, 'quiz', 'Quiz 1', 85.00, 100.00),
(1, 3, 'assignment', 'Essay 1', 90.00, 100.00),
(2, 2, 'test', 'Midterm Exam', 78.50, 100.00),
(2, 4, 'assignment', 'Lab Report 1', 92.00, 100.00),
(3, 1, 'quiz', 'Quiz 1', 75.00, 100.00),
(3, 5, 'project', 'Art Analysis Project', 88.00, 100.00),
(4, 3, 'assignment', 'Essay 1', 95.00, 100.00),
(4, 5, 'test', 'Midterm Exam', 82.50, 100.00),
(5, 2, 'quiz', 'Quiz 1', 79.00, 100.00),
(5, 4, 'assignment', 'Lab Report 1', 85.50, 100.00); 