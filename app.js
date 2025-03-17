/**
 * Student Management System - Main JavaScript
 * 
 * This file contains all the core JavaScript functionality for the Student Management System,
 * including authentication, data loading, and UI interactions.
 */

// DOM Content Loaded Event
document.addEventListener('DOMContentLoaded', function() {
    // Check login status and initialize UI
    checkLoginStatus();
    
    // Initialize UI components
    createAlertContainer();
    
    // Add event listeners for sidebar toggler
    const sidebarToggler = document.querySelector('.sidebar-toggler');
    if (sidebarToggler) {
        sidebarToggler.addEventListener('click', toggleSidebar);
    }
    
    // Add event listeners for forms
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    const studentForm = document.getElementById('studentForm');
    if (studentForm) {
        studentForm.addEventListener('submit', handleStudentForm);
    }
    
    const courseForm = document.getElementById('courseForm');
    if (courseForm) {
        courseForm.addEventListener('submit', handleCourseForm);
    }
    
    // Load data based on current page
    const currentPage = window.location.pathname.split('/').pop();
    
    if (currentPage === 'dashboard.html') {
        loadDashboardStats();
    } else if (currentPage === 'students.html') {
        loadStudents();
    } else if (currentPage === 'courses.html') {
        loadCourses();
    } else if (currentPage === 'enrollment.html') {
        loadEnrollments();
    }
});

/**
 * Check Login Status
 * 
 * Verifies if the user is logged in and redirects accordingly
 */
function checkLoginStatus() {
    const publicPages = ['login.html', 'register.html', 'index.html'];
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    
    fetch('../backend/auth.php?action=check_login')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const isLoggedIn = data.logged_in;
                
                // Redirect to login if not logged in and trying to access protected page
                if (!isLoggedIn && !publicPages.includes(currentPage)) {
                    window.location.href = 'login.html';
                }
                
                // Redirect to dashboard if logged in and trying to access public page
                if (isLoggedIn && publicPages.includes(currentPage)) {
                    window.location.href = 'dashboard.html';
                }
                
                // Update UI with user info if logged in
                if (isLoggedIn && data.user) {
                    const userLinks = document.querySelectorAll('.nav-link:has(i.fa-user-circle)');
                    userLinks.forEach(link => {
                        link.innerHTML = `<i class="fas fa-user-circle"></i> ${data.user.username}`;
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error checking login status:', error);
        });
}

/**
 * Handle Login
 * 
 * Processes the login form submission
 */
function handleLogin(event) {
    event.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        showAlert('error', 'Username and password are required');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'login');
    formData.append('username', username);
    formData.append('password', password);
    
    fetch('../backend/auth.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Login successful! Redirecting...');
            setTimeout(() => {
                window.location.href = 'dashboard.html';
            }, 1500);
        } else {
            showAlert('error', data.message || 'Login failed');
        }
    })
    .catch(error => {
        console.error('Error during login:', error);
        showAlert('error', 'An error occurred');
    });
}

/**
 * Handle Register
 * 
 * Processes the registration form submission
 */
function handleRegister(event) {
    event.preventDefault();
    
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const role = document.getElementById('role').value;
    
    // Validate inputs
    if (!username || !email || !password || !confirmPassword || !role) {
        showAlert('error', 'All fields are required');
        return;
    }
    
    if (password !== confirmPassword) {
        showAlert('error', 'Passwords do not match');
        return;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showAlert('error', 'Invalid email address');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'register');
    formData.append('username', username);
    formData.append('email', email);
    formData.append('password', password);
    formData.append('role', role);
    
    fetch('../backend/auth.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Registration successful! Redirecting to login...');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 1500);
        } else {
            showAlert('error', data.message || 'Registration failed');
        }
    })
    .catch(error => {
        console.error('Error during registration:', error);
        showAlert('error', 'An error occurred');
    });
}

/**
 * Load Students
 * 
 * Fetches and displays the list of students
 */
function loadStudents() {
    fetch('../backend/student.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const studentList = document.querySelector('.student-list tbody');
                studentList.innerHTML = '';
                
                if (data.students.length > 0) {
                    data.students.forEach(student => {
                        studentList.innerHTML += `
                            <tr>
                                <td>${student.id}</td>
                                <td>${student.name}</td>
                                <td>${student.email}</td>
                                <td>${student.phone || '-'}</td>
                                <td>${student.enrolled_date}</td>
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="viewStudent(${student.id})">View</button>
                                    <button class="btn btn-primary btn-sm" onclick="editStudent(${student.id})">Edit</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteStudent(${student.id})">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    studentList.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center">No students found</td>
                        </tr>
                    `;
                }
            } else {
                showAlert('error', data.message || 'Failed to load students');
            }
        })
        .catch(error => {
            console.error('Error loading students:', error);
            showAlert('error', 'An error occurred');
        });
}

/**
 * View Student
 * 
 * Shows detailed information for a specific student
 */
function viewStudent(id) {
    fetch(`../backend/student.php?action=get&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const student = data.student;
                const modal = document.getElementById('studentViewModal');
                
                // Populate modal with student data
                document.getElementById('studentViewName').textContent = student.name;
                document.getElementById('studentViewEmail').textContent = student.email;
                document.getElementById('studentViewPhone').textContent = student.phone || 'N/A';
                document.getElementById('studentViewAddress').textContent = student.address || 'N/A';
                document.getElementById('studentViewDOB').textContent = student.date_of_birth || 'N/A';
                document.getElementById('studentViewEnrolled').textContent = student.enrolled_date;
                
                // Show modal
                modal.style.display = 'block';
            } else {
                showAlert('error', data.message || 'Failed to load student data');
            }
        })
        .catch(error => {
            console.error('Error viewing student:', error);
            showAlert('error', 'An error occurred');
        });
}

/**
 * Edit Student
 * 
 * Redirects to the student form page for editing
 */
function editStudent(id) {
    window.location.href = `student-form.html?id=${id}`;
}

/**
 * Handle Student Form
 * 
 * Processes the student form submission (add/edit)
 */
function handleStudentForm(event) {
    event.preventDefault();
    
    const studentId = document.getElementById('studentId').value;
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const address = document.getElementById('address').value;
    const dateOfBirth = document.getElementById('dateOfBirth').value;
    const gender = document.getElementById('gender').value;
    const status = document.getElementById('status').value;
    const notes = document.getElementById('notes').value;
    
    // Validate required fields
    if (!name || !email) {
        showAlert('error', 'Name and email are required');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', studentId ? 'update' : 'add');
    if (studentId) {
        formData.append('id', studentId);
    }
    formData.append('name', name);
    formData.append('email', email);
    formData.append('phone', phone);
    formData.append('address', address);
    formData.append('date_of_birth', dateOfBirth);
    formData.append('gender', gender);
    formData.append('status', status);
    formData.append('notes', notes);
    
    fetch('../backend/student.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `Student ${studentId ? 'updated' : 'added'} successfully`);
            setTimeout(() => {
                window.location.href = 'students.html';
            }, 1500);
        } else {
            showAlert('error', data.message || `Failed to ${studentId ? 'update' : 'add'} student`);
        }
    })
    .catch(error => {
        console.error('Error submitting student form:', error);
        showAlert('error', 'An error occurred');
    });
}

/**
 * Delete Student
 * 
 * Deletes a student record after confirmation
 */
function deleteStudent(id) {
    if (confirm('Are you sure you want to delete this student?')) {
        fetch(`../backend/student.php?action=delete&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', 'Student deleted successfully');
                    loadStudents(); // Reload the student list
                } else {
                    showAlert('error', data.message || 'Failed to delete student');
                }
            })
            .catch(error => {
                console.error('Error deleting student:', error);
                showAlert('error', 'An error occurred');
            });
    }
}

/**
 * Load Courses
 * 
 * Fetches and displays the list of courses
 */
function loadCourses() {
    fetch('../backend/course.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const courseList = document.querySelector('.course-list tbody');
                courseList.innerHTML = '';
                
                if (data.courses && data.courses.length > 0) {
                    data.courses.forEach(course => {
                        courseList.innerHTML += `
                            <tr>
                                <td>${course.id}</td>
                                <td>${course.name}</td>
                                <td>${course.code}</td>
                                <td>${course.credits}</td>
                                <td>${course.instructor}</td>
                                <td><span class="badge ${course.status === 'active' ? 'bg-success' : 'bg-secondary'}">${course.status}</span></td>
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="viewCourse(${course.id})">View</button>
                                    <button class="btn btn-primary btn-sm" onclick="editCourse(${course.id})">Edit</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteCourse(${course.id})">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    courseList.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center">No courses found</td>
                        </tr>
                    `;
                }
            } else {
                showAlert('error', data.message || 'Failed to load courses');
            }
        })
        .catch(error => {
            console.error('Error loading courses:', error);
            showAlert('error', 'An error occurred while loading courses');
        });
}

/**
 * Handle Course Form
 * 
 * Processes the course form submission (add/edit)
 */
function handleCourseForm(event) {
    event.preventDefault();
    
    const courseId = document.getElementById('courseId').value;
    const name = document.getElementById('name').value;
    const code = document.getElementById('code').value;
    const credits = document.getElementById('credits').value;
    const instructor = document.getElementById('instructor').value;
    const description = document.getElementById('description').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const status = document.getElementById('status').value;
    const capacity = document.getElementById('capacity').value;
    
    // Validate required fields
    if (!name || !code || !credits || !instructor) {
        showAlert('error', 'Name, code, credits, and instructor are required');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', courseId ? 'update' : 'add');
    if (courseId) {
        formData.append('id', courseId);
    }
    formData.append('name', name);
    formData.append('code', code);
    formData.append('credits', credits);
    formData.append('instructor', instructor);
    formData.append('description', description);
    formData.append('start_date', startDate);
    formData.append('end_date', endDate);
    formData.append('status', status);
    formData.append('capacity', capacity);
    
    fetch('../backend/course.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', `Course ${courseId ? 'updated' : 'added'} successfully`);
            setTimeout(() => {
                window.location.href = 'courses.html';
            }, 1500);
        } else {
            showAlert('error', data.message || `Failed to ${courseId ? 'update' : 'add'} course`);
        }
    })
    .catch(error => {
        console.error('Error submitting course form:', error);
        showAlert('error', 'An error occurred');
    });
}

/**
 * Load Enrollments
 * 
 * Fetches and displays the list of enrollments
 */
function loadEnrollments() {
    fetch('../backend/enrollment.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const enrollmentList = document.querySelector('.enrollment-list tbody');
                enrollmentList.innerHTML = '';
                
                if (data.enrollments && data.enrollments.length > 0) {
                    data.enrollments.forEach(enrollment => {
                        // Create status badge class
                        let statusBadgeClass = '';
                        switch (enrollment.status) {
                            case 'active':
                                statusBadgeClass = 'bg-success';
                                break;
                            case 'completed':
                                statusBadgeClass = 'bg-primary';
                                break;
                            case 'dropped':
                                statusBadgeClass = 'bg-danger';
                                break;
                            default:
                                statusBadgeClass = 'bg-secondary';
                        }
                        
                        enrollmentList.innerHTML += `
                            <tr>
                                <td>${enrollment.id}</td>
                                <td>${enrollment.student_name}</td>
                                <td>${enrollment.course_name}</td>
                                <td>${enrollment.enrollment_date}</td>
                                <td><span class="badge ${statusBadgeClass}">${enrollment.status}</span></td>
                                <td>
                                    <button class="btn btn-primary btn-sm" onclick="editEnrollment(${enrollment.id})">Edit</button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteEnrollment(${enrollment.id})">Delete</button>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    enrollmentList.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center">No enrollments found</td>
                        </tr>
                    `;
                }
            } else {
                showAlert('error', data.message || 'Failed to load enrollments');
            }
        })
        .catch(error => {
            console.error('Error loading enrollments:', error);
            showAlert('error', 'An error occurred');
        });
}

/**
 * Load Dashboard Stats
 * 
 * Fetches and displays the dashboard statistics
 */
function loadDashboardStats() {
    fetch('../backend/dashboard.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.stats;
                
                // Update counter cards
                document.getElementById('totalStudents').textContent = stats.totalStudents;
                document.getElementById('totalCourses').textContent = stats.totalCourses;
                document.getElementById('activeEnrollments').textContent = stats.activeEnrollments;
                
                // Update charts if the update function exists
                if (typeof window.updateCharts === 'function') {
                    window.updateCharts(stats);
                }
                
                // Load recent students is handled separately in dashboard.html
            } else {
                showAlert('error', data.message || 'Failed to load dashboard statistics');
            }
        })
        .catch(error => {
            console.error('Error loading dashboard stats:', error);
            showAlert('error', 'An error occurred while loading dashboard statistics');
        });
}

/**
 * Toggle Sidebar
 * 
 * Shows/hides the sidebar on mobile devices
 */
function toggleSidebar() {
    document.querySelector('.dashboard').classList.toggle('sidebar-open');
}

/**
 * Show Alert
 * 
 * Displays an alert message to the user
 */
function showAlert(type, message) {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) return;
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <span class="alert-message">${message}</span>
        <button type="button" class="close" onclick="this.parentElement.remove();">
            <span>&times;</span>
        </button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentElement) {
            alert.remove();
        }
    }, 5000);
}

/**
 * Create Alert Container
 * 
 * Creates a container for displaying alerts if it doesn't exist
 */
function createAlertContainer() {
    if (!document.getElementById('alertContainer')) {
        const alertContainer = document.createElement('div');
        alertContainer.id = 'alertContainer';
        alertContainer.className = 'alert-container';
        document.body.appendChild(alertContainer);
    }
}

/**
 * Logout
 * 
 * Logs the user out and redirects to the login page
 */
function logout() {
    fetch('../backend/auth.php?action=logout')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'login.html';
            } else {
                showAlert('error', 'Logout failed');
            }
        })
        .catch(error => {
            console.error('Error during logout:', error);
            showAlert('error', 'An error occurred');
        });
} 