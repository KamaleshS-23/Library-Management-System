<?php
// At the VERY TOP of manage_users.php (before any output)
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        $user_id = intval($_POST['user_id']);
        $user_type = $_POST['user_type'];
        $is_approved = isset($_POST['is_approved']) ? 1 : 0;
        $membership_level = $_POST['membership_level'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET 
                                  user_type = ?, 
                                  is_approved = ?,
                                  membership_level = ?,
                                  is_active = ?,
                                  updated_at = NOW()
                                  WHERE id = ?");
            $stmt->execute([$user_type, $is_approved, $membership_level, $is_active, $user_id]);
            
            $_SESSION['success'] = "User updated successfully!";
            header("Location: manage_users.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update user: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        try {
            // Prevent deleting yourself
            if ($user_id == $_SESSION['owner_id']) {
                $_SESSION['error'] = "You cannot delete your own account!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                $_SESSION['success'] = "User deleted successfully!";
            }
            
            header("Location: manage_users.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to delete user: " . $e->getMessage();
        }
    }
    
}

// Get all users
$users = [];
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to load users: " . $e->getMessage();
}

$admin_requests = [];
try {
    // Get users
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get pending admin requests
    $stmt = $pdo->query("SELECT ar.*, u.username 
                        FROM admin_requests ar
                        LEFT JOIN users u ON ar.user_id = u.id
                        WHERE ar.status = 'pending'
                        ORDER BY ar.created_at DESC");
    $admin_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Failed to load data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Library Management System</title>
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
        
        /* Users Table */
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .users-table th, .users-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .users-table th {
            background-color: var(--dark-color);
            color: white;
            font-weight: 500;
        }
        
        .users-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-admin {
            background-color: var(--warning-color);
            color: white;
        }
        
        .badge-user {
            background-color: var(--primary-color);
            color: white;
        }
        
        .badge-super-admin {
            background-color: var(--danger-color);
            color: white;
        }
        
        .badge-active {
            background-color: var(--success-color);
            color: white;
        }
        
        .badge-inactive {
            background-color: var(--gray-color);
            color: white;
        }
        
        .badge-pending {
            background-color: var(--warning-color);
            color: white;
        }
        
        .badge-approved {
            background-color: var(--success-color);
            color: white;
        }
        
        .badge-rejected {
            background-color: var(--danger-color);
            color: white;
        }
        
        .action-btns {
            display: flex;
            gap: 5px;
        }
        
        .btn {
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
        
        .btn-edit {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #2980b9;
        }
        
        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #c0392b;
        }
        
        .btn-add {
            background-color: var(--success-color);
            color: white;
            margin-bottom: 20px;
        }
        
        .btn-add:hover {
            background-color: #27ae60;
        }
        
        /* User Form Modal */
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
            width: 600px;
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
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Search and Filter */
        .user-controls {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 15px;
        }
        
        .search-box {
            flex-grow: 1;
            max-width: 400px;
        }
        
        .search-box input {
            width: 100%;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filter-controls {
            display: flex;
            gap: 10px;
        }
        
        .filter-controls select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .user-controls {
                flex-direction: column;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .users-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 480px) {
            .action-btns {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
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
                <a href="manage_users.php" class="nav-item active"><i class="fas fa-users"></i> Manage Users</a>
                <a href="system_settings.php" class="nav-item"><i class="fas fa-cog"></i> System Settings</a>
                <a href="reports.php" class="nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
            </nav>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <div class="header">
                <h1>Manage Users</h1>
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
            
            <div class="user-controls">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search users...">
                </div>
                <div class="filter-controls">
                    <select id="userTypeFilter">
                        <option value="">All Types</option>
                        <option value="user">Regular Users</option>
                        <option value="admin">Admins</option>
                        <option value="super_admin">Super Admins</option>
                    </select>
                    <select id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="pending">Pending Approval</option>
                    </select>
                </div>
            </div>
            
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Membership</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No users found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?= !empty($user['profile_pic']) ? htmlspecialchars($user['profile_pic']) : 'https://ui-avatars.com/api/?name='.urlencode($user['full_name']).'&background=random' ?>" 
                                         alt="User Avatar" class="user-avatar">
                                    <div>
                                        <strong><?= htmlspecialchars($user['full_name']) ?></strong><br>
                                        <small>@<?= htmlspecialchars($user['username']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php if ($user['user_type'] === 'admin'): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php elseif ($user['user_type'] === 'super_admin'): ?>
                                    <span class="badge badge-super-admin">Super Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-user">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['request_status'] === 'pending'): ?>
                                    <span class="badge badge-pending">Pending</span>
                                <?php elseif ($user['request_status'] === 'rejected'): ?>
                                    <span class="badge badge-rejected">Rejected</span>
                                <?php elseif ($user['is_active']): ?>
                                    <span class="badge badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= ucfirst($user['membership_level'] ?? 'basic') ?>
                            </td>
                            <td>
                                <?= date('M d, Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <button class="btn btn-edit" onclick="openEditUserModal(<?= $user['id'] ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-delete" onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['full_name'])) ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Edit User</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="userForm" method="POST">
                <input type="hidden" id="user_id" name="user_id" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="full_name" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" id="email" readonly>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="username" readonly>
                    </div>
                    <div class="form-group">
                        <label for="user_type">User Type *</label>
                        <select id="user_type" name="user_type" required>
                            <option value="user">Regular User</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="membership_level">Membership Level *</label>
                        <select id="membership_level" name="membership_level" required>
                            <option value="basic">Basic</option>
                            <option value="premium">Premium</option>
                            <option value="gold">Gold</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; gap: 15px; align-items: center;">
                        <div>
                            <input type="checkbox" id="is_approved" name="is_approved" value="1">
                            <label for="is_approved">Approved</label>
                        </div>
                        <div>
                            <input type="checkbox" id="is_active" name="is_active" value="1">
                            <label for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-edit" name="update_user">Update User</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Open modal for editing user
        function openEditUserModal(userId) {
            fetch('get_user_details.php?id=' + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalTitle').textContent = 'Edit User: ' + data.user.full_name;
                        document.getElementById('user_id').value = data.user.id;
                        document.getElementById('full_name').value = data.user.full_name;
                        document.getElementById('email').value = data.user.email;
                        document.getElementById('username').value = data.user.username;
                        document.getElementById('user_type').value = data.user.user_type;
                        document.getElementById('membership_level').value = data.user.membership_level || 'basic';
                        document.getElementById('is_approved').checked = data.user.is_approved == 1;
                        document.getElementById('is_active').checked = data.user.is_active == 1;
                        
                        document.getElementById('userModal').style.display = 'flex';
                    } else {
                        alert(data.message || 'Failed to load user details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading user details');
                });
        }
        
        // Confirm before deleting user
        function confirmDelete(userId, userName) {
            if (confirm(`Are you sure you want to delete "${userName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'manage_users.php';
                
                const inputId = document.createElement('input');
                inputId.type = 'hidden';
                inputId.name = 'user_id';
                inputId.value = userId;
                form.appendChild(inputId);
                
                const inputDelete = document.createElement('input');
                inputDelete.type = 'hidden';
                inputDelete.name = 'delete_user';
                inputDelete.value = '1';
                form.appendChild(inputDelete);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.users-table tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[0].textContent.toLowerCase();
                const email = row.cells[1].textContent.toLowerCase();
                const username = row.querySelector('small').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || email.includes(searchTerm) || username.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // User type filter
        document.getElementById('userTypeFilter').addEventListener('change', function() {
            filterUsers();
        });
        
        // Status filter
        document.getElementById('statusFilter').addEventListener('change', function() {
            filterUsers();
        });
        
        // Combined filter function
        function filterUsers() {
            const userType = document.getElementById('userTypeFilter').value;
            const status = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.users-table tbody tr');
            
            rows.forEach(row => {
                const rowType = row.cells[2].querySelector('.badge').textContent.toLowerCase().replace(' ', '_');
                const rowStatus = row.cells[3].querySelector('.badge').textContent.toLowerCase();
                
                let showRow = true;
                
                // Apply user type filter
                if (userType && !rowType.includes(userType)) {
                    showRow = false;
                }
                
                // Apply status filter
                if (status === 'active' && rowStatus !== 'active') {
                    showRow = false;
                } else if (status === 'inactive' && rowStatus !== 'inactive') {
                    showRow = false;
                } else if (status === 'pending' && rowStatus !== 'pending') {
                    showRow = false;
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }
    </script>
</body>
</html>