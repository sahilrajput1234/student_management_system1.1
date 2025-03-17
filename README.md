# Student Management System

A comprehensive web-based Student Management System that helps educational institutions manage students, courses, enrollments, attendance, and grades efficiently. It features a responsive, modern UI with a secure PHP backend and MySQL database.

## Features

- **User Authentication**: Secure login and registration system with role-based access control
- **Dashboard**: Overview of key metrics with charts and recent activities
- **Student Management**: Add, edit, view, and delete student records
- **Course Management**: Create and manage courses with detailed information
- **Enrollment**: Enroll students in courses and manage enrollments
- **Responsive Design**: Works seamlessly on desktop and mobile devices

## System Requirements

- PHP 7.4+ with PDO extension
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache, Nginx, etc.)
- Modern web browser (Chrome, Firefox, Safari, Edge)

## Installation

### Option 1: Manual Setup

1. **Download or Clone the Repository**
   - Download the ZIP file and extract it, or use git to clone the repository:
   ```
   git clone https://github.com/yourusername/student-management-system.git
   ```

2. **Configure Database**
   - Create a new MySQL database
   - Update database credentials in `backend/config.php` if necessary:
   ```php
   define('DB_HOST', 'localhost');  // Your database host
   define('DB_USER', 'root');       // Your database username
   define('DB_PASS', '');           // Your database password
   define('DB_NAME', 'student_management_system'); // Your database name
   ```

3. **Import Database Schema**
   - Import the SQL schema from `database/setup.sql` into your database using phpMyAdmin or MySQL command line

### Option 2: Automated Setup

1. **Copy Files to Web Server**
   - Copy all files to your web server's document root or a subdirectory

2. **Run Installation Script**
   - Open your web browser and navigate to the installation script:
   ```
   http://localhost/studentmanagementsystem/database/install.php
   ```
   - Follow the on-screen instructions to complete the database setup

## First Login

After installation, you can log in with the default admin account:
- **Username**: admin
- **Password**: admin123

⚠️ **Important**: Change this password immediately after your first login!

## System Structure

- **frontend/**: Contains HTML, CSS, and JavaScript files for the user interface
- **backend/**: Contains PHP scripts that handle server-side logic
- **database/**: Contains SQL scripts and database-related utilities

## Pages/Modules

1. **Authentication**
   - Login
   - Registration
   - Password recovery

2. **Dashboard**
   - Overview statistics
   - Recent activities
   - Quick access to key functions

3. **Student Management**
   - List all students
   - Add new students
   - Edit student information
   - View student details
   - Delete students
   - Search and filter students

4. **Course Management**
   - List all courses
   - Add new courses
   - Edit course information
   - View course details
   - Delete courses
   - Search and filter courses

5. **Enrollment Management**
   - Enroll students in courses
   - View and manage enrollments
   - Generate enrollment reports

## Security Considerations

- All user passwords are hashed using PHP's password_hash() function
- SQL injection prevention using prepared statements
- Cross-site scripting (XSS) prevention with input sanitization
- Form validation on both client and server sides

## Customization

You can customize the system according to your requirements:

- Modify the CSS in `frontend/style.css` to change the appearance
- Add or remove fields from student or course forms by editing the corresponding HTML files and PHP scripts
- Add new modules by creating new frontend pages and backend scripts

## Troubleshooting

### Common Issues:

1. **Database Connection Error**
   - Verify database credentials in `backend/config.php`
   - Ensure MySQL service is running

2. **Blank Page or PHP Errors**
   - Check PHP error logs
   - Ensure PHP version meets requirements

3. **Login Issues**
   - Clear browser cookies
   - Verify database contains admin user record

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contact

For questions or support, please create an issue in the GitHub repository or contact the project maintainer.