<?php
/**
 * Dashboard Statistics
 * 
 * Provides statistics and metrics for the dashboard display in the Student Management System.
 */

// Include database configuration
require_once 'config.php';

// Get database connection
$conn = getDBConnection();
if (!$conn instanceof PDO) {
    sendError($conn); // Connection error message
}

// Handle the request based on the action parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'stats':
        getDashboardStats();
        break;
        
    default:
        sendError('Invalid action');
}

/**
 * Get Dashboard Statistics
 * 
 * Gathers various statistics about students, courses, and enrollments
 * for display on the dashboard.
 */
function getDashboardStats() {
    global $conn;
    
    try {
        // Get total number of students
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM students");
        $stmt->execute();
        $totalStudents = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get total number of courses
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses");
        $stmt->execute();
        $totalCourses = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get total active enrollments
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM enrollments WHERE status = 'active'");
        $stmt->execute();
        $activeEnrollments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get student enrollment by month (last 6 months)
        $stmt = $conn->prepare("
            SELECT 
                DATE_FORMAT(enrollment_date, '%Y-%m') as month,
                COUNT(*) as count
            FROM enrollments
            WHERE enrollment_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
            GROUP BY month
            ORDER BY month ASC
        ");
        $stmt->execute();
        $enrollmentsByMonth = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format enrollment data for chart
        $enrollmentLabels = [];
        $enrollmentValues = [];
        $monthNames = [
            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'May', '06' => 'Jun',
            '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec'
        ];
        
        foreach ($enrollmentsByMonth as $enrollment) {
            $month = explode('-', $enrollment['month'])[1]; // Extract month from 'YYYY-MM'
            $enrollmentLabels[] = $monthNames[$month];
            $enrollmentValues[] = (int)$enrollment['count'];
        }
        
        // Get course distribution
        $stmt = $conn->prepare("
            SELECT 
                c.name,
                COUNT(e.id) as student_count
            FROM courses c
            LEFT JOIN enrollments e ON c.id = e.course_id
            GROUP BY c.id
            ORDER BY student_count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $courseDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format course data for chart
        $courseLabels = [];
        $courseValues = [];
        
        foreach ($courseDistribution as $course) {
            $courseLabels[] = $course['name'];
            $courseValues[] = (int)$course['student_count'];
        }
        
        // Prepare the complete stats data
        $stats = [
            'totalStudents' => $totalStudents,
            'totalCourses' => $totalCourses,
            'activeEnrollments' => $activeEnrollments,
            'enrollmentData' => [
                'labels' => $enrollmentLabels,
                'values' => $enrollmentValues
            ],
            'courseData' => [
                'labels' => $courseLabels,
                'values' => $courseValues
            ]
        ];
        
        sendResponse(true, ['stats' => $stats]);
    } catch (PDOException $e) {
        sendError('Database error: ' . $e->getMessage());
    }
} 