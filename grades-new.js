// Check authentication
if (!localStorage.getItem('token')) {
    window.location.href = 'index.html';
}

// Initialize data
let grades = [];
let students = [];
let courses = [];

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', initialize);

// Initialize data and setup event listeners
async function initialize() {
    try {
        await Promise.all([
            fetchGrades(),
            fetchStudents(),
            fetchCourses()
        ]);

        setupEventListeners();
        updateDashboardStats();
        renderGradesGrid();
    } catch (error) {
        console.error('Error initializing:', error);
        showNotification('Error loading data. Please try again.', 'error');
    }
}

// Setup event listeners
function setupEventListeners() {
    document.getElementById('searchInput').addEventListener('input', debounce(renderGradesGrid, 300));
    document.getElementById('courseFilter').addEventListener('change', renderGradesGrid);
    document.getElementById('gradeFilter').addEventListener('change', renderGradesGrid);
    document.getElementById('gradeInput').addEventListener('input', updateGradeSlider);
    document.getElementById('gradeSlider').addEventListener('input', updateGradeInput);
}

// Fetch data functions
async function fetchGrades() {
    const response = await fetch('../backend/grades.php', {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
    });
    grades = await response.json();
}

async function fetchStudents() {
    const response = await fetch('../backend/student.php', {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
    });
    students = await response.json();
    populateStudentSelect();
}

async function fetchCourses() {
    const response = await fetch('../backend/course.php', {
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
    });
    courses = await response.json();
    populateCoursesSelect();
}

// Update dashboard statistics
function updateDashboardStats() {
    const totalStudents = new Set(grades.map(g => g.student_id)).size;
    const averageGrade = grades.reduce((acc, curr) => acc + curr.grade, 0) / grades.length || 0;
    const passRate = (grades.filter(g => g.grade >= 60).length / grades.length * 100) || 0;

    document.getElementById('totalStudents').textContent = totalStudents;
    document.getElementById('averageGrade').textContent = averageGrade.toFixed(1);
    document.getElementById('passRate').textContent = `${passRate.toFixed(1)}%`;

    // Animate the numbers
    animateValue('totalStudents', 0, totalStudents, 1000);
    animateValue('averageGrade', 0, averageGrade, 1000);
    animateValue('passRate', 0, passRate, 1000);
}

// Render grades grid
function renderGradesGrid() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const courseFilter = document.getElementById('courseFilter').value;
    const gradeFilter = document.getElementById('gradeFilter').value;

    const filteredGrades = grades.filter(grade => {
        const student = students.find(s => s.id === grade.student_id);
        const course = courses.find(c => c.id === grade.course_id);
        
        const matchesSearch = (
            student?.name.toLowerCase().includes(searchTerm) ||
            course?.name.toLowerCase().includes(searchTerm)
        );
        const matchesCourse = !courseFilter || grade.course_id === parseInt(courseFilter);
        const matchesGrade = !gradeFilter || (
            gradeFilter === '90' && grade.grade >= 90 ||
            gradeFilter === '80' && grade.grade >= 80 && grade.grade < 90 ||
            gradeFilter === '70' && grade.grade >= 70 && grade.grade < 80 ||
            gradeFilter === '60' && grade.grade >= 60 && grade.grade < 70 ||
            gradeFilter === '0' && grade.grade < 60
        );

        return matchesSearch && matchesCourse && matchesGrade;
    });

    const gradesGrid = document.getElementById('gradesGrid');
    gradesGrid.innerHTML = '';

    filteredGrades.forEach(grade => {
        const student = students.find(s => s.id === grade.student_id);
        const course = courses.find(c => c.id === grade.course_id);
        const gradeCard = createGradeCard(grade, student, course);
        gradesGrid.appendChild(gradeCard);
    });
}

// Create grade card element
function createGradeCard(grade, student, course) {
    const card = document.createElement('div');
    card.className = 'grade-card';
    card.innerHTML = `
        <div class="grade-header">
            <h3>${student?.name || 'Unknown Student'}</h3>
            <span class="grade ${getGradeClass(grade.grade)}">${grade.grade}</span>
        </div>
        <div class="grade-body">
            <p class="course-name">${course?.name || 'Unknown Course'}</p>
            <p class="grade-date">Last Updated: ${new Date(grade.updated_at).toLocaleDateString()}</p>
            ${grade.comments ? `<p class="grade-comments">${grade.comments}</p>` : ''}
        </div>
        <div class="grade-actions">
            <button onclick="editGrade(${grade.id})" class="edit-btn">
                <i class="fas fa-edit"></i> Edit
            </button>
            <button onclick="deleteGrade(${grade.id})" class="delete-btn">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    `;
    return card;
}

// Modal functions
function showGradeForm() {
    document.getElementById('gradeFormModal').style.display = 'block';
    document.getElementById('gradeForm').reset();
    document.getElementById('gradeId').value = '';
    updateGradeSlider();
}

function closeGradeForm() {
    document.getElementById('gradeFormModal').style.display = 'none';
}

// Edit grade
function editGrade(gradeId) {
    const grade = grades.find(g => g.id === gradeId);
    if (!grade) return;

    document.getElementById('gradeId').value = grade.id;
    document.getElementById('studentSelect').value = grade.student_id;
    document.getElementById('courseSelect').value = grade.course_id;
    document.getElementById('gradeInput').value = grade.grade;
    document.getElementById('gradeSlider').value = grade.grade;
    document.getElementById('commentsInput').value = grade.comments || '';

    showGradeForm();
}

// Delete grade
async function deleteGrade(gradeId) {
    if (!confirm('Are you sure you want to delete this grade?')) return;

    try {
        const response = await fetch(`../backend/grades.php?id=${gradeId}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
        });

        if (response.ok) {
            grades = grades.filter(g => g.id !== gradeId);
            updateDashboardStats();
            renderGradesGrid();
            showNotification('Grade deleted successfully', 'success');
        } else {
            throw new Error('Failed to delete grade');
        }
    } catch (error) {
        console.error('Error deleting grade:', error);
        showNotification('Error deleting grade. Please try again.', 'error');
    }
}

// Handle grade form submission
async function handleGradeSubmit(event) {
    event.preventDefault();

    const gradeId = document.getElementById('gradeId').value;
    const gradeData = {
        student_id: parseInt(document.getElementById('studentSelect').value),
        course_id: parseInt(document.getElementById('courseSelect').value),
        grade: parseInt(document.getElementById('gradeInput').value),
        comments: document.getElementById('commentsInput').value
    };

    try {
        const response = await fetch('../backend/grades.php', {
            method: gradeId ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify(gradeId ? { ...gradeData, id: gradeId } : gradeData)
        });

        if (response.ok) {
            await fetchGrades();
            updateDashboardStats();
            renderGradesGrid();
            closeGradeForm();
            showNotification(`Grade ${gradeId ? 'updated' : 'added'} successfully`, 'success');
        } else {
            throw new Error(`Failed to ${gradeId ? 'update' : 'add'} grade`);
        }
    } catch (error) {
        console.error('Error saving grade:', error);
        showNotification(`Error ${gradeId ? 'updating' : 'adding'} grade. Please try again.`, 'error');
    }
}

// Utility functions
function getGradeClass(grade) {
    if (grade >= 90) return 'grade-a';
    if (grade >= 80) return 'grade-b';
    if (grade >= 70) return 'grade-c';
    if (grade >= 60) return 'grade-d';
    return 'grade-f';
}

function populateStudentSelect() {
    const studentSelect = document.getElementById('studentSelect');
    studentSelect.innerHTML = students.map(student =>
        `<option value="${student.id}">${student.name}</option>`
    ).join('');
}

function populateCoursesSelect() {
    const courseSelect = document.getElementById('courseSelect');
    const courseFilter = document.getElementById('courseFilter');
    const courseOptions = courses.map(course =>
        `<option value="${course.id}">${course.name}</option>`
    ).join('');
    
    courseSelect.innerHTML = courseOptions;
    courseFilter.innerHTML = `<option value="">All Courses</option>${courseOptions}`;
}

function updateGradeSlider() {
    const gradeInput = document.getElementById('gradeInput');
    const gradeSlider = document.getElementById('gradeSlider');
    gradeSlider.value = gradeInput.value;
}

function updateGradeInput() {
    const gradeInput = document.getElementById('gradeInput');
    const gradeSlider = document.getElementById('gradeSlider');
    gradeInput.value = gradeSlider.value;
}

function showNotification(message, type) {
    // Implementation of notification system can be added here
    alert(message);
}

function animateValue(elementId, start, end, duration) {
    const element = document.getElementById(elementId);
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;

    const animate = () => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            element.textContent = end.toFixed(1);
            return;
        }
        element.textContent = current.toFixed(1);
        requestAnimationFrame(animate);
    };

    animate();
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Logout function
function logout() {
    localStorage.removeItem('token');
    window.location.href = 'index.html';
}