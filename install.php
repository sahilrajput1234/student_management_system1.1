<?php
/**
 * Database Installation Script
 * 
 * This script creates the database and required tables for the Student Management System.
 * It should be run once during the initial setup.
 */

// Define database configuration
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'student_management_system';

// Connect to MySQL without selecting a database
try {
    $conn = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Student Management System - Database Installation</h1>";
    
    // Create the database if it doesn't exist
    try {
        $sql = "CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $conn->exec($sql);
        echo "<p>Database created successfully or already exists.</p>";
    } catch(PDOException $e) {
        echo "<p>Error creating database: " . $e->getMessage() . "</p>";
        exit();
    }
    
    // Select the database
    $conn->exec("USE $dbName");
    
    // Read the SQL file
    $sqlFile = file_get_contents(__DIR__ . '/setup.sql');
    
    if ($sqlFile === false) {
        echo "<p>Error reading setup.sql file.</p>";
        exit();
    }
    
    // Split the SQL file into separate statements
    $statements = explode(';', $sqlFile);
    
    // Execute each statement
    $success = true;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        if (!empty($statement)) {
            try {
                $conn->exec($statement);
            } catch(PDOException $e) {
                echo "<p>Error executing SQL: " . $e->getMessage() . "</p>";
                echo "<p>Statement: " . $statement . "</p>";
                $success = false;
                break;
            }
        }
    }
    
    if ($success) {
        echo "<p>Database tables created successfully.</p>";
        echo "<p>Sample data has been loaded.</p>";
        echo "<p>Default admin user:<br>Username: admin<br>Password: admin123</p>";
        echo "<p>Installation completed successfully. <a href='../frontend/index.html'>Click here</a> to go to the login page.</p>";
    }
    
} catch(PDOException $e) {
    echo "<p>Connection failed: " . $e->getMessage() . "</p>";
}