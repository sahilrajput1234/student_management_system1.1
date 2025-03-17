<?php
/**
 * Enrollment Management
 * 
 * This file handles enrollment operations including listing, adding, updating,
 * searching, and deleting enrollment records.
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
    case 'list':
        listEnrollments();
        break;
        
    case 'get':
        getEnrollment();
        break;
        
    case 'add':
        addEnrollment();
        break;
        
    case 'update':
        updateEnrollment();
        break;
        
    case 'delete':
        deleteEnrollment();
        break;
        
    case 'search':
        searchEnrollments();
        break;
        
    default:
        sendError('Invalid action');
}

/**
 * List enrollments
 * 
 * Returns a list of enrollments with optional status filtering
 */
function listEnrollments() {
    global $conn;
    
    try {
        $sql = "SELECT e.*, s.name as student_name, c.name as course_name 
                FROM enrollments e
                JOIN students s ON e.student_id = s.id
                JOIN courses c ON e.course_id = c.id";
        
        // Add status filter if provided
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $status = sanitize($_GET['status']);
            $sql .= " WHERE e.status = :status";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':status', $status);
        } else {
            $stmt = $conn->prepare($sql);
        }
        
        $stmt->execute();
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, ['enrollments' => $enrollments]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Get a specific enrollment
 * 
 * Returns details of a specific enrollment by ID
 */
function getEnrollment() {
    global $conn;
    
    // Check if enrollment ID is provided
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        sendError('Enrollment ID is required');
    }
    
    $id = (int) $_GET['id'];
    
    try {
        $stmt = $conn->prepare("SELECT e.*, s.name as student_name, c.name as course_name 
                                FROM enrollments e
                                JOIN students s ON e.student_id = s.id
                                JOIN courses c ON e.course_id = c.id
                                WHERE e.id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
            sendResponse(true, ['enrollment' => $enrollment]);
        } else {
            sendError('Enrollment not found');
        }
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Add a new enrollment
 * 
 * Creates a new enrollment record
 */
function addEnrollment() {
    global $conn;
    
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Invalid request method');
    }
    
    // Required fields
    $requiredFields = ['student_id', 'course_id'];
    
    // Check if all required fields are provided
    if (!validateRequiredFields($requiredFields, $_POST)) {
        sendError('Student and course are required');
    }
    
    // Sanitize and validate input
    $studentId = (int) $_POST['student_id'];
    $courseId = (int) $_POST['course_id'];
    $enrollmentDate = isset($_POST['enrollment_date']) && !empty($_POST['enrollment_date']) 
                    ? $_POST['enrollment_date'] 
                    : date('Y-m-d');
    $status = isset($_POST['status']) && !empty($_POST['status']) 
            ? sanitize($_POST['status']) 
            : 'active';
    
    try {
        // Check if student exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE id = :id");
        $stmt->bindParam(':id', $studentId);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendError('Student not found');
        }
        
        // Check if course exists
        $stmt = $conn->prepare("SELECT id FROM courses WHERE id = :id");
        $stmt->bindParam(':id', $courseId);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendError('Course not found');
        }
        
        // Check if enrollment already exists
        $stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = :student_id AND course_id = :course_id");
        $stmt->bindParam(':student_id', $studentId);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            sendError('Student is already enrolled in this course');
        }
        
        // Insert new enrollment
        $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id, enrollment_date, status) 
                               VALUES (:student_id, :course_id, :enrollment_date, :status)");
        $stmt->bindParam(':student_id', $studentId);
        $stmt->bindParam(':course_id', $courseId);
        $stmt->bindParam(':enrollment_date', $enrollmentDate);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            $enrollmentId = $conn->lastInsertId();
            sendResponse(true, ['id' => $enrollmentId], 'Enrollment created successfully');
        } else {
            sendError('Failed to create enrollment');
        }
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Update an existing enrollment
 * 
 * Updates an enrollment record with new data
 */
function updateEnrollment() {
    global $conn;
    
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Invalid request method');
    }
    
    // Check if enrollment ID is provided
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        sendError('Enrollment ID is required');
    }
    
    $id = (int) $_POST['id'];
    $enrollmentDate = isset($_POST['enrollment_date']) && !empty($_POST['enrollment_date']) 
                    ? $_POST['enrollment_date'] 
                    : null;
    $status = isset($_POST['status']) && !empty($_POST['status']) 
            ? sanitize($_POST['status']) 
            : null;
    
    try {
        // Check if enrollment exists
        $stmt = $conn->prepare("SELECT id FROM enrollments WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendError('Enrollment not found');
        }
        
        // Prepare update SQL
        $sql = "UPDATE enrollments SET ";
        $params = [];
        
        if ($enrollmentDate !== null) {
            $sql .= "enrollment_date = :enrollment_date, ";
            $params[':enrollment_date'] = $enrollmentDate;
        }
        
        if ($status !== null) {
            $sql .= "status = :status, ";
            $params[':status'] = $status;
        }
        
        // Remove trailing comma and space
        $sql = rtrim($sql, ", ");
        
        // Add WHERE clause
        $sql .= " WHERE id = :id";
        $params[':id'] = $id;
        
        // Only proceed if we have fields to update
        if (count($params) > 1) { // More than just the ID
            $stmt = $conn->prepare($sql);
            
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value);
            }
            
            if ($stmt->execute()) {
                sendResponse(true, [], 'Enrollment updated successfully');
            } else {
                sendError('Failed to update enrollment');
            }
        } else {
            sendError('No fields to update');
        }
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Delete an enrollment
 * 
 * Removes an enrollment record from the database
 */
function deleteEnrollment() {
    global $conn;
    
    // Check if enrollment ID is provided
    if (!isset($_GET['id']) && !isset($_POST['id'])) {
        sendError('Enrollment ID is required');
    }
    
    $id = isset($_GET['id']) ? (int) $_GET['id'] : (int) $_POST['id'];
    
    try {
        // Check if enrollment exists
        $stmt = $conn->prepare("SELECT id FROM enrollments WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendError('Enrollment not found');
        }
        
        // Delete the enrollment
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE id = :id");
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            sendResponse(true, [], 'Enrollment deleted successfully');
        } else {
            sendError('Failed to delete enrollment');
        }
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Search enrollments
 * 
 * Searches for enrollments based on student name, course name, or status
 */
function searchEnrollments() {
    global $conn;
    
    // Check if search query is provided
    if (!isset($_GET['query']) || empty($_GET['query'])) {
        sendError('Search query is required');
    }
    
    $query = '%' . sanitize($_GET['query']) . '%';
    
    try {
        $stmt = $conn->prepare("SELECT e.*, s.name as student_name, c.name as course_name 
                                FROM enrollments e
                                JOIN students s ON e.student_id = s.id
                                JOIN courses c ON e.course_id = c.id
                                WHERE s.name LIKE :query 
                                OR c.name LIKE :query 
                                OR e.status LIKE :query
                                OR c.code LIKE :query");
        $stmt->bindParam(':query', $query);
        $stmt->execute();
        
        $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, ['enrollments' => $enrollments]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
} 