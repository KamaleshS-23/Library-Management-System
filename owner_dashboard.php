<?php
// At the VERY TOP of owner_dashboard.php (before any output)
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

// Get system statistics
// Get system statistics
$stats = [];
try {
    // Total books
    $stmt = $pdo->query("SELECT COUNT(*) as total_books FROM books");
    $stats['total_books'] = $stmt->fetchColumn();
    
    // Active users (using is_approved)
    $stmt = $pdo->query("SELECT COUNT(*) as active_users FROM users WHERE is_approved = TRUE");
    $stats['active_users'] = $stmt->fetchColumn();
    
    // Admin users count
    $stmt = $pdo->query("SELECT COUNT(*) as admin_users FROM users WHERE user_type = 'admin'");
    $stats['admin_users'] = $stmt->fetchColumn();
    
    // Current loans
    $stmt = $pdo->query("SELECT COUNT(*) as current_loans FROM borrowings WHERE return_date IS NULL");
    $stats['current_loans'] = $stmt->fetchColumn();
    
    // Overdue books
    $stmt = $pdo->query("SELECT COUNT(*) as overdue_books FROM borrowings WHERE return_date IS NULL AND due_date < CURDATE()");
    $stats['overdue_books'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to load statistics: " . $e->getMessage();
    error_log("Database error in dashboard: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard | Library Management System</title>
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
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .stat-card h3 {
            margin: 0;
            font-size: 14px;
            color: var(--gray-color);
        }
        
        .stat-card p {
            margin: 5px 0 0;
            font-size: 24px;
            font-weight: bold;
        }
        
        /* Requests Section */
        .section-title {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .requests-list {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .request-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s;
        }
        
        .request-item:hover {
            background-color: #f9f9f9;
        }
        
        .request-item:last-child {
            border-bottom: none;
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .request-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-approve {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-approve:hover {
            background-color: #27ae60;
        }
        
        .btn-reject {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-reject:hover {
            background-color: #c0392b;
        }
        
        .btn-view {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-view:hover {
            background-color: #2980b9;
        }
        
        .no-requests {
            padding: 20px;
            text-align: center;
            color: var(--gray-color);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--gray-color);
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .request-actions {
                flex-direction: column;
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
                <a href="owner_dashboard.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_books.php" class="nav-item"><i class="fas fa-book"></i> Manage Books</a>
                <a href="manage_users.php" class="nav-item"><i class="fas fa-users"></i> Manage Users</a>
                <a href="reports.php" class="nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
            </nav>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <div class="header">
                <h1>Dashboard Overview</h1>
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
            
            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <i class="fas fa-book" style="color: var(--primary-color);"></i>
                    <h3>Total Books</h3>
                    <p><?= number_format($stats['total_books'] ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-users" style="color: var(--success-color);"></i>
                    <h3>Active Users</h3>
                    <p><?= number_format($stats['active_users'] ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-user-shield" style="color: var(--warning-color);"></i>
                    <h3>Admin Users</h3>
                    <p><?= number_format($stats['admin_users'] ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-exchange-alt" style="color: var(--gray-color);"></i>
                    <h3>Current Loans</h3>
                    <p><?= number_format($stats['current_loans'] ?? 0) ?></p>
                </div>
            </div>
                        
            <!-- Pending Admin Requests -->
            <h2 class="section-title">Pending Admin Requests</h2>
            <div class="requests-list">
                <?php if (empty($requests)): ?>
                    <div class="no-requests">
                        <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success-color); margin-bottom: 15px;"></i>
                        <p>No pending admin requests</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                    <div class="request-item" id="request-<?= $request['id'] ?>">
                        <div class="request-header">
                            <h3><?= htmlspecialchars($request['full_name']) ?></h3>
                            <span class="request-date"><?= date('M d, Y', strtotime($request['created_at'])) ?></span>
                        </div>
                        <p><strong>Email:</strong> <?= htmlspecialchars($request['email']) ?></p>
                        <p><strong>Position:</strong> <?= htmlspecialchars($request['position']) ?></p>
                        <p><strong>Reason:</strong> <?= htmlspecialchars($request['reason']) ?></p>
                        <?php if ($request['user_id']): ?>
                            <p><strong>Existing User:</strong> <?= htmlspecialchars($request['username']) ?></p>
                        <?php endif; ?>
                        <div class="request-actions">
                            <button class="btn btn-view" onclick="viewRequestDetails(<?= $request['id'] ?>)">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            <button class="btn btn-approve" onclick="approveRequest(<?= $request['id'] ?>)">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="btn btn-reject" onclick="rejectRequest(<?= $request['id'] ?>)">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Request Details Modal -->
    <div class="modal" id="requestModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Request Details</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer" id="modalFooter">
                <!-- Buttons will be loaded dynamically -->
            </div>
        </div>
    </div>
    
    <script>
        // View request details
        function viewRequestDetails(requestId) {
            fetch('get_request_details.php?id=' + requestId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalTitle').textContent = 'Request from ' + data.request.full_name;
                        
                        let modalBody = `
                            <p><strong>Email:</strong> ${data.request.email}</p>
                            <p><strong>Position:</strong> ${data.request.position}</p>
                            <p><strong>Submitted:</strong> ${new Date(data.request.created_at).toLocaleDateString()}</p>
                            <p><strong>Reason:</strong></p>
                            <p>${data.request.reason}</p>
                        `;
                        
                        if (data.request.user_id) {
                            modalBody += `<p><strong>Existing User:</strong> ${data.request.username || 'N/A'}</p>`;
                        }
                        
                        document.getElementById('modalBody').innerHTML = modalBody;
                        
                        let modalFooter = `
                            <button class="btn btn-approve" onclick="approveRequest(${data.request.id})">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button class="btn btn-reject" onclick="rejectRequest(${data.request.id})">
                                <i class="fas fa-times"></i> Reject
                            </button>
                            <button class="btn" onclick="closeModal()">
                                <i class="fas fa-times"></i> Close
                            </button>
                        `;
                        
                        document.getElementById('modalFooter').innerHTML = modalFooter;
                        document.getElementById('requestModal').style.display = 'flex';
                    } else {
                        alert(data.message || 'Failed to load request details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading request details');
                });
        }
        
        // Approve request
        function approveRequest(requestId) {
            if (confirm('Are you sure you want to approve this admin request?')) {
                fetch('approve_admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ request_id: requestId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Request approved successfully!');
                        document.getElementById('request-' + requestId).remove();
                        closeModal();
                        
                        // Reload if no requests left
                        if (document.querySelectorAll('.request-item').length === 0) {
                            location.reload();
                        }
                    } else {
                        alert(data.message || 'Approval failed');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while approving the request');
                });
            }
        }
        
        // Reject request
        function rejectRequest(requestId) {
            const reason = prompt('Please enter reason for rejection (optional):');
            
            if (reason !== null) { // User didn't click cancel
                fetch('reject_admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        request_id: requestId,
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Request rejected successfully!');
                        document.getElementById('request-' + requestId).remove();
                        closeModal();
                        
                        // Reload if no requests left
                        if (document.querySelectorAll('.request-item').length === 0) {
                            location.reload();
                        }
                    } else {
                        alert(data.message || 'Rejection failed');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while rejecting the request');
                });
            }
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('requestModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('requestModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>