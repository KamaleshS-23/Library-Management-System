<?php
// At the VERY TOP of reports.php (before any output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';
require_once 'check_auth.php';
verifyOwnerSession();

// Verify owner is logged in
if (!isset($_SESSION['owner_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: owner_login.html');
    exit();
}

// Set default session values if not set
$_SESSION['owner_name'] = $_SESSION['owner_name'] ?? 'Admin';
$_SESSION['role'] = $_SESSION['role'] ?? '';

// Initialize variables
$report_type = $_GET['report'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_data = [];

// Generate reports based on type
try {
    switch ($report_type) {
        case 'user_management':
            // User Management Report
            $stmt = $pdo->prepare("
                SELECT 
                    id, full_name, email, username, 
                    user_type, membership_level, 
                    is_active, is_approved, 
                    DATE(created_at) as join_date,
                    last_login
                FROM users
                WHERE created_at BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)
                ORDER BY created_at DESC
            ");
            $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'book_management':
            // Book Management Report
            $stmt = $pdo->prepare("
                SELECT 
                    id, title, author, isbn, category,
                    quantity, available_quantity,
                    (quantity - available_quantity) as borrowed_count,
                    created_at
                FROM books
                ORDER BY title
            ");
            $stmt->execute();
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'circulation':
            // Circulation Report
            $stmt = $pdo->prepare("
                SELECT 
                    b.id as book_id, b.title, b.author,
                    u.id as user_id, u.full_name, u.email,
                    br.borrow_date, br.due_date, br.return_date,
                    CASE 
                        WHEN br.return_date IS NULL AND br.due_date < CURDATE() THEN 'Overdue'
                        WHEN br.return_date IS NULL THEN 'Borrowed'
                        ELSE 'Returned'
                    END as status,
                    DATEDIFF(IFNULL(br.return_date, CURDATE()), br.borrow_date) as days_borrowed
                FROM borrowings br
                JOIN books b ON br.book_id = b.id
                JOIN users u ON br.user_id = u.id
                WHERE br.borrow_date BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)
                ORDER BY br.borrow_date DESC
            ");
            $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'financial':
            // Financial Report
            $stmt = $pdo->prepare("
                SELECT 
                    f.id, u.full_name, u.email,
                    b.title as book_title,
                    f.amount, f.reason, f.status,
                    f.issued_date, f.payment_date,
                    p.payment_method, p.transaction_date
                FROM fines f
                LEFT JOIN payments p ON f.id = p.fine_id
                JOIN users u ON f.user_id = u.id
                LEFT JOIN borrowings br ON f.borrowing_id = br.id
                LEFT JOIN books b ON br.book_id = b.id
                WHERE f.issued_date BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)
                ORDER BY f.issued_date DESC
            ");
            $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'system_usage':
            // System Usage Report
            $stmt = $pdo->prepare("
                SELECT 
                    DATE(borrow_date) as activity_date,
                    COUNT(*) as total_borrowings,
                    SUM(CASE WHEN return_date IS NULL AND due_date >= CURDATE() THEN 1 ELSE 0 END) as active_borrowings,
                    SUM(CASE WHEN return_date IS NULL AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue_borrowings,
                    (SELECT COUNT(*) FROM reservations WHERE DATE(reservation_date) = activity_date) as reservations,
                    (SELECT COUNT(*) FROM users WHERE DATE(created_at) = activity_date) as new_users
                FROM borrowings
                WHERE borrow_date BETWEEN :start_date AND DATE_ADD(:end_date, INTERVAL 1 DAY)
                GROUP BY activity_date
                ORDER BY activity_date DESC
            ");
            $stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'special':
            // Special Reports
            $stmt = $pdo->prepare("
                SELECT 
                    b.id, b.title, b.author,
                    COUNT(DISTINCT br.id) as borrow_count,
                    COUNT(DISTINCT r.id) as reservation_count,
                    COUNT(DISTINCT w.id) as wishlist_count,
                    COUNT(DISTINCT e.id) as download_count,
                    AVG(rv.rating) as avg_rating,
                    COUNT(DISTINCT rv.id) as review_count
                FROM books b
                LEFT JOIN borrowings br ON b.id = br.book_id
                LEFT JOIN reservations r ON b.id = r.book_id
                LEFT JOIN wishlist w ON b.id = w.book_id
                LEFT JOIN ebooks e ON b.id = e.book_id
                LEFT JOIN reviews rv ON b.id = rv.book_id
                GROUP BY b.id
                ORDER BY borrow_count DESC, reservation_count DESC
                LIMIT 50
            ");
            $stmt->execute();
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to generate report: " . $e->getMessage();
    error_log("Database error in reports: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Library Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        :root {
            --primary-color: #3498db;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --dark-color: #2c3e50;
            --light-color: #ecf0f1;
            --gray-color: #95a5a6;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-item:hover, .nav-item.active {
            background-color: rgba(255,255,255,0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .logout-btn {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .logout-btn:hover {
            background-color: #c0392b;
        }
        
        /* Report Controls */
        .report-controls {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .report-types {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .report-type-btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            background-color: var(--light-color);
            color: var(--dark-color);
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .report-type-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .report-type-btn.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .date-range {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .date-range input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .generate-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .generate-btn:hover {
            background-color: #27ae60;
        }
        
        /* Report Results */
        .report-results {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .report-title {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .report-table th, .report-table td {
            padding: 10px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .report-table th {
            background-color: var(--dark-color);
            color: white;
        }
        
        .report-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .no-results {
            text-align: center;
            padding: 20px;
            color: var(--gray-color);
        }
        
        .export-options {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .export-btn {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .export-btn.csv {
            background-color: var(--success-color);
            color: white;
        }
        
        .export-btn.excel {
            background-color: #2e7d32;
            color: white;
        }
        
        .export-btn.pdf {
            background-color: var(--danger-color);
            color: white;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .report-types {
                flex-direction: column;
            }
            
            .date-range {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Library Admin</h2>
                <p>Owner Dashboard</p>
            </div>
            <nav class="sidebar-nav">
                <a href="owner_dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_books.php" class="nav-item"><i class="fas fa-book"></i> Manage Books</a>
                <a href="manage_users.php" class="nav-item"><i class="fas fa-users"></i> Manage Users</a>
                <a href="system_settings.php" class="nav-item"><i class="fas fa-cog"></i> System Settings</a>
                <a href="reports.php" class="nav-item active"><i class="fas fa-chart-bar"></i> Reports</a>
            </nav>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <div class="header">
                <h1>Library Reports</h1>
                <div class="user-info">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['owner_name']) ?>&background=random" alt="User Avatar">
                    <span><?= htmlspecialchars($_SESSION['owner_name']) ?></span>
                    <button class="logout-btn" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </div>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <div class="report-controls">
                <h3>Generate Report</h3>
                
                <div class="report-types">
                    <a href="reports.php?report=user_management&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>"
                       class="report-type-btn <?= $report_type === 'user_management' ? 'active' : '' ?>">
                       <i class="fas fa-users"></i> User Management
                    </a>
                    <a href="reports.php?report=book_management&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>"
                       class="report-type-btn <?= $report_type === 'book_management' ? 'active' : '' ?>">
                       <i class="fas fa-book"></i> Book Management
                    </a>
                    <a href="reports.php?report=circulation&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>"
                       class="report-type-btn <?= $report_type === 'circulation' ? 'active' : '' ?>">
                       <i class="fas fa-exchange-alt"></i> Circulation
                    </a>
                    <a href="reports.php?report=financial&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>"
                       class="report-type-btn <?= $report_type === 'financial' ? 'active' : '' ?>">
                       <i class="fas fa-money-bill-wave"></i> Financial
                    </a>
                    <a href="reports.php?report=system_usage&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>"
                       class="report-type-btn <?= $report_type === 'system_usage' ? 'active' : '' ?>">
                       <i class="fas fa-chart-line"></i> System Usage
                    </a>
                    <a href="reports.php?report=special&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>"
                       class="report-type-btn <?= $report_type === 'special' ? 'active' : '' ?>">
                       <i class="fas fa-star"></i> Special Reports
                    </a>
                </div>
                
                <form method="get" action="reports.php">
                    <input type="hidden" name="report" value="<?= $report_type ?>">
                    <div class="date-range">
                        <label for="start_date">From:</label>
                        <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>">
                        
                        <label for="end_date">To:</label>
                        <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>">
                        
                        <button type="submit" class="generate-btn">
                            <i class="fas fa-sync-alt"></i> Generate
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="report-results">
                <?php if ($report_type): ?>
                    <h3 class="report-title">
                        <?php 
                            $report_titles = [
                                'user_management' => 'User Management Report',
                                'book_management' => 'Book Management Report',
                                'circulation' => 'Circulation Report',
                                'financial' => 'Financial Report',
                                'system_usage' => 'System Usage Report',
                                'special' => 'Special Reports'
                            ];
                            echo $report_titles[$report_type] ?? 'Report';
                        ?>
                        <small>(<?= date('M d, Y', strtotime($start_date)) ?> to <?= date('M d, Y', strtotime($end_date)) ?>)</small>
                    </h3>
                    
                    <?php if (!empty($report_data)): ?>
                        <div class="export-options">
                            <button class="export-btn csv" onclick="exportReport('csv')">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                            <button class="export-btn excel" onclick="exportReport('excel')">
                                <i class="fas fa-file-excel"></i> Export Excel
                            </button>
                            <button class="export-btn pdf" onclick="exportReport('pdf')">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($report_data[0]) as $column): ?>
                                            <th><?= ucwords(str_replace('_', ' ', $column)) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $value): ?>
                                                <td><?= htmlspecialchars($value) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-results">
                            <i class="fas fa-info-circle" style="font-size: 48px; color: var(--gray-color); margin-bottom: 15px;"></i>
                            <p>No data found for this report in the selected date range.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-chart-bar" style="font-size: 48px; color: var(--gray-color); margin-bottom: 15px;"></i>
                        <p>Please select a report type to generate.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function exportReport(format) {
            // Get current URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            
            // Add export format parameter
            urlParams.set('export', format);
            
            // Redirect to same page with export parameter
            window.location.href = 'reports.php?' + urlParams.toString();
        }
        
        // Set default dates if not set
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.getElementById('start_date').value) {
                document.getElementById('start_date').value = '<?= date('Y-m-01') ?>';
            }
            if (!document.getElementById('end_date').value) {
                document.getElementById('end_date').value = '<?= date('Y-m-d') ?>';
            }
        });
    </script>
</body>
</html>