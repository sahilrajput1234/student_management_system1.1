<?php
/**
 * Student Management
 * 
 * Handles operations related to students, including listing, adding, updating, 
 * searching, and deleting student records.
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
        listStudents();
        break;
        
    case 'get':
        getStudent();
        break;
        
    case 'add':
        addStudent();
        break;
        
    case 'update':
        updateStudent();
        break;
        
    case 'delete':
        deleteStudent();
        break;
        
    case 'search':
        searchStudents();
        break;
        
    case 'recent':
        getRecentStudents();
        break;
        
    default:
        sendError('Invalid action');
}

/**
 * List all students with optional filtering
 */
function listStudents() {
    global $conn;
    
    try {
        $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
        $query = "SELECT * FROM students";
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
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, ['students' => $students]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Get a specific student by ID
 */
function getStudent() {
    global $conn;
    
    // Check if ID is provided
    if (!isset($_GET['id'])) {
        sendError('Student ID is required');
    }
    
    $studentId = (int)$_GET['id'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = :id");
        $stmt->bindParam(':id', $studentId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendError('Student not found');
        }
        
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        sendResponse(true, ['student' => $student]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Add a new student
 */
function addStudent() {
    global $conn;
    
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Invalid request method');
    }
    
    // Required fields for adding a student
    $requiredFields = ['name', 'email'];
    
    // Check if all required fields are provided
    if (!validateRequiredFields($requiredFields, $_POST)) {
        sendError('Name and email are required');
    }
    
    // Sanitize input
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
    $address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
    $dateOfBirth = isset($_POST['date_of_birth']) ? sanitize($_POST['date_of_birth']) : null;
    $gender = isset($_POST['gender']) ? sanitize($_POST['gender']) : null;
    $status = isset($_POST['status']) ? sanitize($_POST['status']) : 'active';
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format');
    }
    
    try {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            sendError('Email already exists');
        }
        
        // Insert the new student
        $stmt = $conn->prepare("
            INSERT INTO students (name, email, phone, address, date_of_birth, gender, status, notes)
            VALUES (:name, :email, :phone, :address, :date_of_birth, :gender, :status, :notes)
        ");
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':date_of_birth', $dateOfBirth);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':notes', $notes);
        
        $stmt->execute();
        
        // Get the ID of the newly inserted student
        $studentId = $conn->lastInsertId();
        
        sendResponse(true, ['id' => $studentId], 'Student added successfully');
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Update an existing student
 */
function updateStudent() {
    global $conn;
    
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Invalid request method');
    }
    
    // Required fields for updating a student
    $requiredFields = ['id', 'name', 'email'];
    
    // Check if all required fields are provided
    if (!validateRequiredFields($requiredFields, $_POST)) {
        sendError('ID, name, and email are required');
    }
    
    // Sanitize input
    $studentId = (int)$_POST['id'];
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
    $address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
    $dateOfBirth = isset($_POST['date_of_birth']) ? sanitize($_POST['date_of_birth']) : null;
    $gender = isset($_POST['gender']) ? sanitize($_POST['gender']) : null;
    $status = isset($_POST['status']) ? sanitize($_POST['status']) : 'active';
    $notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format');
    }
    
    try {
        // Check if the student exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE id = :id");
        $stmt->bindParam(':id', $studentId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendError('Student not found');
        }
        
        // Check if email already exists (excluding this student)
        $stmt = $conn->prepare("SELECT id FROM students WHERE email = :email AND id != :id");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $studentId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            sendError('Email already exists for another student');
        }
        
        // Update the student
        $stmt = $conn->prepare("
            UPDATE students 
            SET name = :name, 
                email = :email, 
                phone = :phone, 
                address = :address, 
                date_of_birth = :date_of_birth, 
                gender = :gender, 
                status = :status, 
                notes = :notes 
            WHERE id = :id
        ");
        
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':date_of_birth', $dateOfBirth);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':id', $studentId, PDO::PARAM_INT);
        
        $stmt->execute();
        
        sendResponse(true, [], 'Student updated successfully');
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Delete a student
 */
function deleteStudent() {
    global $conn;
    
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Invalid request method');
    }
    
    // Check if ID is provided
    if (!isset($_POST['id'])) {
        sendError('Student ID is required');
    }
    
    $studentId = (int)$_POST['id'];
    
    try {
        // Check if the student exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE id = :id");
        $stmt->bindParam(':id', $studentId, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            sendError('Student not found');
        }
        
        // Delete the student
        $stmt = $conn->prepare("DELETE FROM students WHERE id = :id");
        $stmt->bindParam(':id', $studentId, PDO::PARAM_INT);
        $stmt->execute();
        
        sendResponse(true, [], 'Student deleted successfully');
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Search students
 */
function searchStudents() {
    global $conn;
    
    // Check if query parameter is provided
    if (!isset($_GET['query']) || empty($_GET['query'])) {
        sendError('Search query is required');
    }
    
    $query = sanitize($_GET['query']);
    $searchTerm = "%{$query}%";
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM students 
            WHERE name LIKE :query 
               OR email LIKE :query 
               OR phone LIKE :query 
            ORDER BY id DESC
        ");
        
        $stmt->bindParam(':query', $searchTerm);
        $stmt->execute();
        
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, ['students' => $students]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Get recent students (for dashboard)
 */
function getRecentStudents() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM students ORDER BY id DESC LIMIT 5");
        $stmt->execute();
        
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse(true, ['students' => $students]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
} 