<?php
/**
 * Attendance Management
 * 
 * This file handles attendance operations including adding, updating,
 * and retrieving attendance records.
 */

// Include database configuration
require_once 'config.php';

// Start session to check authentication
session_start();

// Get database connection
$conn = getDBConnection();
if (!$conn instanceof PDO) {
    sendError($conn); // Connection error message
}

// Handle the request based on the action parameter
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'getEnrolledStudents':
        getEnrolledStudents();
        break;
    
    case 'saveAttendance':
        saveAttendance();
        break;
    
    case 'getPastAttendance':
        getPastAttendance();
        break;
    
    case 'getAttendanceDetails':
        getAttendanceDetails();
        break;
    
    default:
        sendError('Invalid action');
}

/**
 * Get enrolled students for a course
 * 
 * Retrieves the list of students enrolled in a course,
 * including any existing attendance records for the specified date
 */
function getEnrolledStudents() {
    global $conn;
    
    // Check if course ID and date are provided
    if (!isset($_GET['course_id']) || empty($_GET['course_id'])) {
        sendError('Course ID is required');
    }
    
    if (!isset($_GET['date']) || empty($_GET['date'])) {
        sendError('Date is required');
    }
    
    $courseId = (int) $_GET['course_id'];
    $date = $_GET['date'];
    
    try {
        // Get the course name
        $stmt = $conn->prepare("SELECT name FROM courses WHERE id = :id");
        $stmt->bindParam(':id', $courseId);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendError('Course not found');
        }
        
        $courseName = $stmt->fetch(PDO::FETCH_COLUMN);
        
        // Get enrolled students with any existing attendance for the date
        $sql = "SELECT s.id, s.name, a.status as attendance_status, a.remarks 
                FROM students s
                JOIN enrollments e ON s.id = e.student_id
                LEFT JOIN attendance a ON s.id = a.student_id AND a.course_id = :course_id AND a.attendance_date = :date
                WHERE e.course_id = :course_id AND e.status = 'active'
                ORDER BY s.name";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, [
            'courseName' => $courseName,
            'students' => $students
        ]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Save attendance records
 * 
 * Creates or updates attendance records for a course on a specific date
 */
function saveAttendance() {
    global $conn;
    
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Invalid request method');
    }
    
    // Check required fields
    if (!isset($_POST['course_id']) || empty($_POST['course_id'])) {
        sendError('Course ID is required');
    }
    
    if (!isset($_POST['attendance_date']) || empty($_POST['attendance_date'])) {
        sendError('Attendance date is required');
    }
    
    if (!isset($_POST['attendance']) || !is_array($_POST['attendance'])) {
        sendError('No attendance data provided');
    }
    
    $courseId = (int) $_POST['course_id'];
    $attendanceDate = $_POST['attendance_date'];
    $attendanceData = $_POST['attendance'];
    
    try {
        // Start a transaction
        $conn->beginTransaction();
        
        // First delete any existing attendance records for this course and date
        $stmt = $conn->prepare("DELETE FROM attendance WHERE course_id = :course_id AND attendance_date = :date");
        $stmt->bindParam(':course_id', $courseId);
        $stmt->bindParam(':date', $attendanceDate);
        $stmt->execute();
        
        // Prepare statement for inserting new attendance records
        $insertStmt = $conn->prepare("
            INSERT INTO attendance (student_id, course_id, attendance_date, status, remarks)
            VALUES (:student_id, :course_id, :date, :status, :remarks)
        ");
        
        // Insert new attendance records
        foreach ($attendanceData as $studentId => $data) {
            $status = sanitize($data['status']);
            $remarks = isset($data['remarks']) ? sanitize($data['remarks']) : null;
            
            $insertStmt->bindParam(':student_id', $studentId);
            $insertStmt->bindParam(':course_id', $courseId);
            $insertStmt->bindParam(':date', $attendanceDate);
            $insertStmt->bindParam(':status', $status);
            $insertStmt->bindParam(':remarks', $remarks);
            $insertStmt->execute();
        }
        
        // Commit the transaction
        $conn->commit();
        
        sendResponse(true, [], 'Attendance saved successfully');
    } catch (PDOException $e) {
        // Roll back the transaction on error
        $conn->rollBack();
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Get past attendance records
 * 
 * Retrieves summary of attendance records for courses within a date range
 */
function getPastAttendance() {
    global $conn;
    
    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : null;
    $dateRange = isset($_GET['date_range']) ? $_GET['date_range'] : 'all';
    
    try {
        // Set up date filter based on range
        $dateFilter = '';
        if ($dateRange !== 'all') {
            $days = (int) $dateRange;
            $dateFilter = "AND a.attendance_date >= DATE_SUB(CURRENT_DATE, INTERVAL $days DAY)";
        }
        
        // Set up course filter
        $courseFilter = '';
        $params = [];
        
        if ($courseId) {
            $courseFilter = "AND a.course_id = :course_id";
            $params[':course_id'] = $courseId;
        }
        
        // Query to get summary data
        $sql = "
            SELECT 
                a.attendance_date as date,
                a.course_id,
                c.name as course_name,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused_count
            FROM attendance a
            JOIN courses c ON a.course_id = c.id
            WHERE 1=1 $dateFilter $courseFilter
            GROUP BY a.attendance_date, a.course_id, c.name
            ORDER BY a.attendance_date DESC, c.name
        ";
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters if any
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        
        $stmt->execute();
        $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, ['attendance' => $attendance]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Get attendance details
 * 
 * Retrieves detailed attendance records for a specific course and date
 */
function getAttendanceDetails() {
    global $conn;
    
    // Check if course ID and date are provided
    if (!isset($_GET['course_id']) || empty($_GET['course_id'])) {
        sendError('Course ID is required');
    }
    
    if (!isset($_GET['date']) || empty($_GET['date'])) {
        sendError('Date is required');
    }
    
    $courseId = (int) $_GET['course_id'];
    $date = $_GET['date'];
    
    try {
        // Get the course name
        $stmt = $conn->prepare("SELECT name FROM courses WHERE id = :id");
        $stmt->bindParam(':id', $courseId);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendError('Course not found');
        }
        
        $courseName = $stmt->fetch(PDO::FETCH_COLUMN);
        
        // Get attendance details
        $sql = "
            SELECT a.id, a.student_id, s.name as student_name, a.status, a.remarks
            FROM attendance a
            JOIN students s ON a.student_id = s.id
            WHERE a.course_id = :course_id AND a.attendance_date = :date
            ORDER BY s.name
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->bindParam(':date', $date);
        $stmt->execute();
        
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, [
            'courseName' => $courseName,
            'details' => $details
        ]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
} 