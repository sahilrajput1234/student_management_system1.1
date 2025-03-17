document.addEventListener('DOMContentLoaded', () => {
    // Get student ID from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const studentId = urlParams.get('id');

    if (!studentId) {
        window.location.href = 'students.html';
        return;
    }

    // Initialize GSAP animations
    gsap.from('.student-profile', {
        opacity: 0,
        y: 30,
        duration: 0.8,
        ease: 'power2.out'
    });

    gsap.from('.content-section', {
        opacity: 0,
        y: 20,
        duration: 0.6,
        stagger: 0.2,
        ease: 'power2.out'
    });

    // Fetch student data
    fetchStudentData(studentId);
});

async function fetchStudentData(studentId) {
    try {
        const response = await fetch(`../backend/student.php?id=${studentId}`);
        const data = await response.json();

        if (data.error) {
            throw new Error(data.error);
        }

        updateStudentProfile(data);
        initializeCharts(data);
        loadEnrolledCourses(studentId);
    } catch (error) {
        console.error('Error fetching student data:', error);
        showErrorMessage('Failed to load student data');
    }
}

function updateStudentProfile(data) {
    // Update profile information with animations
    const elements = {
        studentName: data.name,
        studentId: `Student ID: ${data.student_id}`,
        studentEmail: data.email,
        studentPhone: data.phone,
        studentDob: data.date_of_birth,
        studentAddress: data.address
    };

    for (const [id, value] of Object.entries(elements)) {
        const element = document.getElementById(id);
        if (element) {
            gsap.to(element, {
                opacity: 0,
                duration: 0.3,
                onComplete: () => {
                    element.textContent = value;
                    gsap.to(element, {
                        opacity: 1,
                        duration: 0.3
                    });
                }
            });
        }
    }

    // Animate statistics
    animateStatValue('gpa', data.gpa || 0);
    animateStatValue('coursesCount', data.courses_count || 0);
    animateStatValue('attendanceRate', `${data.attendance_rate || 0}%`);
}

function animateStatValue(elementId, value) {
    const element = document.getElementById(elementId);
    const startValue = parseFloat(element.textContent);
    const endValue = parseFloat(value);

    gsap.to(element, {
        textContent: endValue,
        duration: 1.5,
        ease: 'power2.out',
        snap: { textContent: 0.01 },
        stagger: {
            amount: 0.5
        }
    });
}

function initializeCharts(data) {
    // Initialize Grades Chart
    const gradesCtx = document.getElementById('gradesChart').getContext('2d');
    new Chart(gradesCtx, {
        type: 'line',
        data: {
            labels: data.grades_history.map(g => g.period),
            datasets: [{
                label: 'Grades History',
                data: data.grades_history.map(g => g.grade),
                borderColor: '#4a90e2',
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeInOutQuart'
            }
        }
    });

    // Initialize Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(attendanceCtx, {
        type: 'bar',
        data: {
            labels: data.attendance_history.map(a => a.month),
            datasets: [{
                label: 'Attendance Rate',
                data: data.attendance_history.map(a => a.rate),
                backgroundColor: '#27ae60',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            animation: {
                duration: 1500,
                easing: 'easeInOutQuart'
            }
        }
    });
}

async function loadEnrolledCourses(studentId) {
    try {
        const response = await fetch(`../backend/enrollment.php?student_id=${studentId}`);
        const courses = await response.json();

        const coursesGrid = document.getElementById('enrolledCourses');
        coursesGrid.innerHTML = '';

        courses.forEach((course, index) => {
            const courseCard = createCourseCard(course);
            coursesGrid.appendChild(courseCard);

            // Animate course cards
            gsap.from(courseCard, {
                opacity: 0,
                y: 20,
                duration: 0.5,
                delay: index * 0.1,
                ease: 'power2.out'
            });
        });
    } catch (error) {
        console.error('Error loading enrolled courses:', error);
        showErrorMessage('Failed to load enrolled courses');
    }
}

function createCourseCard(course) {
    const card = document.createElement('div');
    card.className = 'course-card';
    card.innerHTML = `
        <h3>${course.name}</h3>
        <p>${course.code}</p>
        <p>Progress: ${course.progress}%</p>
        <div class="progress">
            <div class="progress-bar" style="width: ${course.progress}%"></div>
        </div>
    `;
    return card;
}

function showErrorMessage(message) {
    // Implement error message display
    console.error(message);
}

// Logout functionality
function logout() {
    // Clear session/local storage
    localStorage.removeItem('user');
    sessionStorage.clear();
    
    // Redirect to login page
    window.location.href = 'login.html';
}