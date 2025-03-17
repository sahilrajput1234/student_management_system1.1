<?php
/**
 * Authentication Handler
 * 
 * This file handles user authentication including login, registration, 
 * checking login status, and logout functionality.
 */

// Include database configuration
require_once 'config.php';

// Start or resume the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get database connection
$conn = getDBConnection();
if (!$conn instanceof PDO) {
    sendError($conn); // Connection error message
}

// Handle the request based on the action parameter
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

switch ($action) {
    case 'login':
        handleLogin();
        break;
        
    case 'register':
        handleRegister();
        break;
        
    case 'check_login':
        checkLoginStatus();
        break;
        
    case 'logout':
        handleLogout();
        break;
        
    default:
        sendError('Invalid action');
}

/**
 * Handle user login
 */
function handleLogin() {
    global $conn;
    
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Invalid request method');
    }
    
    // Check if username and password are provided
    if (!isset($_POST['username']) || !isset($_POST['password'])) {
        sendError('Username and password are required');
    }
    
    $username = sanitize($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password as it might alter it
    
    try {
        // Prepare SQL statement to find the user
        $stmt = $conn->prepare("SELECT id, username, password, email, role FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        // Check if user exists
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password (using PHP's password_verify function with hashed passwords)
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Send success response
                sendResponse(true, array(), 'Login successful');
            } else {
                sendError('Invalid password');
            }
        } else {
            sendError('User not found');
        }
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Handle user registration
 */
function handleRegister() {
    global $conn;
    
    // Check if the request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendError('Invalid request method');
    }
    
    // Required fields
    $requiredFields = array('username', 'password', 'email', 'role');
    
    // Check if all required fields are provided
    if (!validateRequiredFields($requiredFields, $_POST)) {
        sendError('All fields are required');
    }
    
    // Sanitize input
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $password = $_POST['password']; // Don't sanitize password
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format');
    }
    
    // Validate role
    $allowedRoles = array('admin', 'teacher', 'student');
    if (!in_array($role, $allowedRoles)) {
        sendError('Invalid role');
    }
    
    try {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            sendError('Username already exists');
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            sendError('Email already exists');
        }
        
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (:username, :password, :email, :role)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->execute();
        
        // Send success response
        sendResponse(true, array(), 'Registration successful');
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
}

/**
 * Check if user is logged in
 */
function checkLoginStatus() {
    // Check if user session exists
    $isLoggedIn = isset($_SESSION['user_id']);
    
    // Prepare response data
    $data = array(
        'logged_in' => $isLoggedIn
    );
    
    // Add user data if logged in
    if ($isLoggedIn) {
        $data['user'] = array(
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role']
        );
    }
    
    // Send response
    sendResponse(true, $data);
}

/**
 * Handle user logout
 */
function handleLogout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Send success response
    sendResponse(true, array(), 'Logout successful');
} 