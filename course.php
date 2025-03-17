<?php
/**
 * Course Management
 * 
 * Handles operations related to courses, including listing, adding, updating, 
 * searching, and deleting course records.
 */

// Include database configuration
require_once 'config.php';

// Get database connection
$conn = getDBConnection();
if (!$conn instanceof PDO) {
    sendError($conn); // Connection error message
}

// Handle the request based on the action parameter
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'list':
        listCourses();
        break;
        
    case 'get':
        getCourse();
        break;
        
    case 'add':
        addCourse();
        break;
        
    case 'update':
        updateCourse();
        break;
        
    case 'delete':
        deleteCourse();
        break;
        
    case 'search':
        searchCourses();
        break;
        
    default:
        sendError('Invalid action');
}

/**
 * List all courses with optional filtering
 */
function listCourses() {
    global $conn;
    
    try {
        $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
        $query = "SELECT * FROM courses";
        $params = [];
        
        // Apply status filter if provided
        if (!empty($status)) {
            $query .= " WHERE status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY id DESC";
        
        $stmt = $conn->prepare($query);
        
        // Bind parameters if they exist
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, ['courses' => $courses]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Get a specific course by ID
 */
function getCourse() {
    global $conn;
    
    // Check if ID is provided
    if (!isset($_GET['id'])) {
        sendError('Course ID is required');
    }
    
    $courseId = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM courses WHERE id = :id");
        $stmt->bindParam(':id', $courseId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendError('Course not found');
        }
        
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        sendResponse(true, ['course' => $course]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Add a new course
 */
function addCourse() {
    global $conn;
    
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Invalid request method');
    }
    
    // Required fields for adding a course
    $requiredFields = ['name', 'code', 'credits'];
    
    // Check if all required fields are provided
    if (!validateRequiredFields($requiredFields, $_POST)) {
        sendError('Name, code, and credits are required');
    }
    
    // Sanitize input
    $name = sanitize($_POST['name']);
    $code = sanitize($_POST['code']);
    $credits = (int)$_POST['credits'];
    $instructor = isset($_POST['instructor']) ? sanitize($_POST['instructor']) : '';
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
    $status = isset($_POST['status']) ? sanitize($_POST['status']) : 'active';
    $startDate = isset($_POST['start_date']) ? sanitize($_POST['start_date']) : null;
    $endDate = isset($_POST['end_date']) ? sanitize($_POST['end_date']) : null;
    $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : null;
    
    // Validate credits
    if ($credits <= 0) {
        sendError('Credits must be a positive number');
    }
    
    try {
        // Check if code already exists
        $stmt = $conn->prepare("SELECT id FROM courses WHERE code = :code");
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            sendError('Course code already exists');
        }
        
        // Insert the new course
        $stmt = $conn->prepare("
            INSERT INTO courses (name, code, credits, instructor, description, status, start_date, end_date, capacity)
            VALUES (:name, :code, :credits, :instructor, :description, :status, :start_date, :end_date, :capacity)
        ");
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':credits', $credits, PDO::PARAM_INT);
        $stmt->bindParam(':instructor', $instructor);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->bindParam(':capacity', $capacity, PDO::PARAM_INT);
        
        $stmt->execute();
        
        // Get the ID of the newly inserted course
        $courseId = $conn->lastInsertId();
        
        sendResponse(true, ['id' => $courseId], 'Course added successfully');
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Update an existing course
 */
function updateCourse() {
    global $conn;
    
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Invalid request method');
    }
    
    // Required fields for updating a course
    $requiredFields = ['id', 'name', 'code', 'credits'];
    
    // Check if all required fields are provided
    if (!validateRequiredFields($requiredFields, $_POST)) {
        sendError('ID, name, code, and credits are required');
    }
    
    // Sanitize input
    $courseId = (int)$_POST['id'];
    $name = sanitize($_POST['name']);
    $code = sanitize($_POST['code']);
    $credits = (int)$_POST['credits'];
    $instructor = isset($_POST['instructor']) ? sanitize($_POST['instructor']) : '';
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
    $status = isset($_POST['status']) ? sanitize($_POST['status']) : 'active';
    $startDate = isset($_POST['start_date']) ? sanitize($_POST['start_date']) : null;
    $endDate = isset($_POST['end_date']) ? sanitize($_POST['end_date']) : null;
    $capacity = isset($_POST['capacity']) ? (int)$_POST['capacity'] : null;
    
    // Validate credits
    if ($credits <= 0) {
        sendError('Credits must be a positive number');
    }
    
    try {
        // Check if the course exists
        $stmt = $conn->prepare("SELECT id FROM courses WHERE id = :id");
        $stmt->bindParam(':id', $courseId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendError('Course not found');
        }
        
        // Check if code already exists (excluding this course)
        $stmt = $conn->prepare("SELECT id FROM courses WHERE code = :code AND id != :id");
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':id', $courseId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            sendError('Course code already exists for another course');
        }
        
        // Update the course
        $stmt = $conn->prepare("
            UPDATE courses 
            SET name = :name, 
                code = :code, 
                credits = :credits, 
                instructor = :instructor, 
                description = :description, 
                status = :status, 
                start_date = :start_date, 
                end_date = :end_date, 
                capacity = :capacity 
            WHERE id = :id
        ");
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':credits', $credits, PDO::PARAM_INT);
        $stmt->bindParam(':instructor', $instructor);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->bindParam(':capacity', $capacity, PDO::PARAM_INT);
        $stmt->bindParam(':id', $courseId, PDO::PARAM_INT);
        
        $stmt->execute();
        
        sendResponse(true, [], 'Course updated successfully');
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Delete a course
 */
function deleteCourse() {
    global $conn;
    
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Invalid request method');
    }
    
    // Check if ID is provided
    if (!isset($_POST['id'])) {
        sendError('Course ID is required');
    }
    
    $courseId = (int)$_POST['id'];
    
    try {
        // Check if the course exists
        $stmt = $conn->prepare("SELECT id FROM courses WHERE id = :id");
        $stmt->bindParam(':id', $courseId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendError('Course not found');
        }
        
        // Delete the course
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = :id");
        $stmt->bindParam(':id', $courseId, PDO::PARAM_INT);
        $stmt->execute();
        
        sendResponse(true, [], 'Course deleted successfully');
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Search courses
 */
function searchCourses() {
    global $conn;
    
    // Check if query parameter is provided
    if (!isset($_GET['query']) || empty($_GET['query'])) {
        sendError('Search query is required');
    }
    
    $query = sanitize($_GET['query']);
    $searchTerm = "%{$query}%";
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM courses 
            WHERE name LIKE :query 
               OR code LIKE :query 
               OR instructor LIKE :query 
            ORDER BY id DESC
        ");
        
        $stmt->bindParam(':query', $searchTerm);
        $stmt->execute();
        
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, ['courses' => $courses]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
} 