<?php
/**
 * Grades API Endpoint
 * 
 * This file handles all grade-related operations including:
 * - Retrieving all grades
 * - Adding a new grade
 * - Updating an existing grade
 * - Deleting a grade
 */

require_once 'config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validate authentication token
if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No authorization token provided']);
    exit();
}

$token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit();
}

// Get database connection
$conn = getDBConnection();
if (!is_object($conn)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different request methods
switch ($method) {
    case 'GET':
        // Retrieve all grades or a specific grade
        $sql = 'SELECT * FROM grades';
        if (isset($_GET['id'])) {
            $sql .= ' WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $_GET['id']);
        } else {
            $stmt = $conn->prepare($sql);
        }
        
        try {
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($result);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error retrieving grades']);
        }
        break;

    case 'POST':
        // Add a new grade
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['student_id']) || !isset($data['course_id']) || !isset($data['grade'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }

        $sql = 'INSERT INTO grades (student_id, course_id, grade, comments) VALUES (:student_id, :course_id, :grade, :comments)';
        $stmt = $conn->prepare($sql);

        try {
            $stmt->execute([
                ':student_id' => $data['student_id'],
                ':course_id' => $data['course_id'],
                ':grade' => $data['grade'],
                ':comments' => $data['comments'] ?? null
            ]);
            
            $data['id'] = $conn->lastInsertId();
            $data['updated_at'] = date('Y-m-d H:i:s');
            echo json_encode($data);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error adding grade']);
        }
        break;

    case 'PUT':
        // Update an existing grade
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['id']) || !isset($data['student_id']) || !isset($data['course_id']) || !isset($data['grade'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit();
        }

        $sql = 'UPDATE grades SET student_id = :student_id, course_id = :course_id, grade = :grade, comments = :comments, updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $stmt = $conn->prepare($sql);

        try {
            $stmt->execute([
                ':id' => $data['id'],
                ':student_id' => $data['student_id'],
                ':course_id' => $data['course_id'],
                ':grade' => $data['grade'],
                ':comments' => $data['comments'] ?? null
            ]);
            
            if ($stmt->rowCount() > 0) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                echo json_encode($data);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Grade not found']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error updating grade']);
        }
        break;

    case 'DELETE':
        // Delete a grade
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing grade ID']);
            exit();
        }

        $sql = 'DELETE FROM grades WHERE id = :id';
        $stmt = $conn->prepare($sql);

        try {
            $stmt->execute([':id' => $_GET['id']]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode(['message' => 'Grade deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Grade not found']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error deleting grade']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}