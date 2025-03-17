// Initialize GSAP animations
gsap.from('.stat-card', {
    duration: 0.8,
    y: 30,
    opacity: 0,
    stagger: 0.2,
    ease: 'power2.out'
});

gsap.from('.report-card', {
    duration: 0.8,
    y: 50,
    opacity: 0,
    stagger: 0.3,
    delay: 0.5,
    ease: 'power2.out'
});

// Chart configurations
const gradeDistribution = {
    labels: ['A', 'B', 'C', 'D', 'F'],
    datasets: [{
        label: 'Number of Students',
        data: [30, 45, 20, 10, 5],
        backgroundColor: [
            'rgba(78, 115, 223, 0.8)',
            'rgba(28, 200, 138, 0.8)',
            'rgba(246, 194, 62, 0.8)',
            'rgba(231, 74, 59, 0.8)',
            'rgba(90, 92, 105, 0.8)'
        ],
        borderWidth: 1
    }]
};

const attendanceTrends = {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
    datasets: [{
        label: 'Attendance Rate',
        data: [95, 88, 92, 85, 90],
        borderColor: 'rgba(78, 115, 223, 1)',
        backgroundColor: 'rgba(78, 115, 223, 0.1)',
        tension: 0.4,
        fill: true
    }]
};

const coursePerformance = {
    labels: ['Mathematics', 'Science', 'History', 'English', 'Computer Science'],
    datasets: [{
        label: 'Average Grade',
        data: [85, 78, 82, 88, 92],
        backgroundColor: 'rgba(78, 115, 223, 0.8)'
    }]
};

// Chart initialization
window.addEventListener('DOMContentLoaded', () => {
    // Grade Distribution Chart
    new Chart(document.getElementById('gradeDistributionChart'), {
        type: 'doughnut',
        data: gradeDistribution,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    });

    // Attendance Trends Chart
    new Chart(document.getElementById('attendanceChart'), {
        type: 'line',
        data: attendanceTrends,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Course Performance Chart
    new Chart(document.getElementById('coursePerformanceChart'), {
        type: 'bar',
        data: coursePerformance,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});

// Chart download functionality
function downloadChart(chartId) {
    const canvas = document.getElementById(chartId);
    const link = document.createElement('a');
    link.download = `${chartId}-report.png`;
    link.href = canvas.toDataURL('image/png');
    link.click();
}

// Time range filter functionality
document.getElementById('timeRange').addEventListener('change', (e) => {
    // Add functionality to update charts based on selected time range
    console.log('Selected time range:', e.target.value);
    // TODO: Implement API call to fetch data for selected time range
});