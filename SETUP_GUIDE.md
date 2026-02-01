# Library Management System - Web Setup Guide

## ✅ Status Check
- **Database Connection**: ✅ Working (localhost:3306)
- **Database**: `library_management` exists and is accessible
- **Code Quality**: No linter errors found
- **Configuration**: Properly configured

## 🚀 Quick Start - Running on Web

### Option 1: PHP Built-in Server (Development)
```bash
cd K:\PROJECTS\projects\dbms_lab_project\library
php -S localhost:8000
```
Then open your browser: `http://localhost:8000`

### Option 2: XAMPP
1. Install XAMPP (if not installed)
2. Copy your project folder to `C:\xampp\htdocs\library`
3. Start Apache and MySQL from XAMPP Control Panel
4. Open browser: `http://localhost/library`

### Option 3: WAMP
1. Install WAMP (if not installed)
2. Copy your project folder to `C:\wamp64\www\library`
3. Start WAMP services
4. Open browser: `http://localhost/library`

## 📋 Database Configuration
Your `config.php` is correctly configured:
- **Host**: localhost
- **Port**: 3306
- **Database**: library_management
- **Username**: root
- **Password**: (configured)

## 📁 Application Structure

### Entry Points
- **Main Entry**: `index.html` - Login/Registration page
- **User Dashboard**: `user_dashboard.html`
- **Admin Dashboard**: `admin_dashboard.php`
- **Owner Dashboard**: `owner_dashboard.php`

### Key Features
- User Registration & Login
- Admin Login
- Book Management
- Borrowing System
- Reservations
- Fines Management
- Reviews & Feedback
- E-books
- Notifications
- Reports

## 🔧 Prerequisites
1. **PHP 7.4+** (with PDO MySQL extension)
2. **MySQL 5.7+** or MariaDB
3. **Web Server** (Apache/Nginx/PHP built-in server)

## ⚠️ Important Notes

1. **Database Tables**: Ensure your database has all required tables:
   - `users`
   - `books`
   - `borrowings`
   - `reservations`
   - `admin_requests`
   - `ebooks`
   - `reviews`
   - `fines`
   - (and others as needed)

2. **Session Configuration**: Sessions are configured in `config.php`

3. **File Permissions**: Ensure PHP can write to session directory (usually handled automatically)

4. **Security**: For production:
   - Change database password
   - Use environment variables for sensitive data
   - Enable HTTPS
   - Set proper file permissions

## 🧪 Testing
Run the database connection test:
```bash
php test_db_connection.php
```

## 📝 Next Steps
1. Start your web server (PHP/Apache)
2. Open `index.html` in your browser
3. Register a new user or login
4. Test all features

## 🆘 Troubleshooting

### Database Connection Issues
- Verify MySQL is running
- Check port in `config.php` (3306 or 3308 for XAMPP)
- Verify database `library_management` exists

### PHP Errors
- Check PHP error logs
- Ensure all required PHP extensions are enabled (PDO, pdo_mysql)

### Session Issues
- Check PHP session directory is writable
- Verify session configuration in `config.php`

---
**Current Status**: ✅ Ready to run on web!
