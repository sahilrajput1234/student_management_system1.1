<?php
/**
 * Database Configuration
 * 
 * This file contains the database connection settings for the Student Management System.
 * Modify these settings according to your database configuration.
 */

// Database credentials
define('DB_HOST', 'localhost');  // Database host
define('DB_USER', 'root');       // Database username
define('DB_PASS', '');           // Database password
define('DB_NAME', 'student_management_system'); // Database name

// Create database connection
function getDBConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
        );
        
        // Set PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $conn;
    } catch(PDOException $e) {
        // Return error message - in production, you might want to log this instead
        return "Connection failed: " . $e->getMessage();
    }
}

/**
 * JSON Response Helper Functions
 */

// Send success response
function sendResponse($success, $data = array(), $message = '') {
    $response = array(
        'success' => $success,
        'message' => $message
    );
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Send error response
function sendError($message = 'An error occurred', $statusCode = 400) {
    http_response_code($statusCode);
    sendResponse(false, array(), $message);
}

/**
 * Security Helper Functions
 */

// Sanitize data
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate required fields
function validateRequiredFields($fields, $data) {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return false;
        }
    }
    return true;
}

// Authorization check
function checkAuth() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        sendError('Unauthorized access', 401);
    }
    return $_SESSION['user_id'];
}

// Check admin role
function checkAdminRole() {
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        sendError('Access denied. Admin privileges required.', 403);
    }
    return $_SESSION['user_id'];
} 