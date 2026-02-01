<?php
require_once('config.php');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html');
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile settings
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $mobile = trim($_POST['mobile']);
        $membership_level = $_POST['membership_level'];

        try {
            $stmt = $pdo->prepare("UPDATE users SET
                                  full_name = ?,
                                  email = ?,
                                  mobile = ?,
                                  membership_level = ?,
                                  updated_at = NOW()
                                  WHERE id = ?");
            $stmt->execute([$full_name, $email, $mobile, $membership_level, $user_id]);
            $success = 'Profile updated successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Failed to update profile: ' . $e->getMessage();
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters long.';
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!password_verify($current_password, $user['password'])) {
                    $errors[] = 'Current password is incorrect.';
                } else {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    $success = 'Password changed successfully!';
                }
            } catch (PDOException $e) {
                $errors[] = 'Failed to change password: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_notifications'])) {
        // Update notification preferences
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("UPDATE users SET
                                  email_notifications = ?,
                                  sms_notifications = ?,
                                  updated_at = NOW()
                                  WHERE id = ?");
            $stmt->execute([$email_notifications, $sms_notifications, $user_id]);
            $success = 'Notification preferences updated successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Failed to update notification preferences: ' . $e->getMessage();
        }
    }
}

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Failed to load user data: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .settings-section {
            margin-bottom: 30px;
        }
        .settings-section h3 {
            color: #111827;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="card">
                <h2 class="section-title">Account Settings</h2>
                <div class="section-content">

                    <?php if (!empty($errors)): ?>
                        <div class="alert error">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert success">
                            <p><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Settings -->
                    <div class="settings-section">
                        <h3><i data-lucide="user" class="inline w-5 h-5 mr-2"></i>Profile Information</h3>
                        <form method="POST" class="space-y-4">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-control"
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control"
                                       value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="mobile">Mobile Number</label>
                                <input type="tel" id="mobile" name="mobile" class="form-control"
                                       value="<?php echo htmlspecialchars($user['mobile'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label for="membership_level">Membership Level</label>
                                <select id="membership_level" name="membership_level" class="form-control">
                                    <option value="basic" <?php echo ($user['membership_level'] ?? 'basic') === 'basic' ? 'selected' : ''; ?>>Basic</option>
                                    <option value="premium" <?php echo ($user['membership_level'] ?? '') === 'premium' ? 'selected' : ''; ?>>Premium</option>
                                    <option value="vip" <?php echo ($user['membership_level'] ?? '') === 'vip' ? 'selected' : ''; ?>>VIP</option>
                                </select>
                            </div>

                            <button type="submit" name="update_profile" class="button">
                                <i data-lucide="save" class="inline w-4 h-4 mr-2"></i>Update Profile
                            </button>
                        </form>
                    </div>

                    <!-- Password Settings -->
                    <div class="settings-section">
                        <h3><i data-lucide="lock" class="inline w-5 h-5 mr-2"></i>Change Password</h3>
                        <form method="POST" class="space-y-4">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>

                            <button type="submit" name="change_password" class="button">
                                <i data-lucide="key" class="inline w-4 h-4 mr-2"></i>Change Password
                            </button>
                        </form>
                    </div>

                    <!-- Notification Settings -->
                    <div class="settings-section">
                        <h3><i data-lucide="bell" class="inline w-5 h-5 mr-2"></i>Notification Preferences</h3>
                        <form method="POST" class="space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="email_notifications" name="email_notifications"
                                       class="mr-3" <?php echo ($user['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                <label for="email_notifications">Receive email notifications for due dates and updates</label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" id="sms_notifications" name="sms_notifications"
                                       class="mr-3" <?php echo ($user['sms_notifications'] ?? 0) ? 'checked' : ''; ?>>
                                <label for="sms_notifications">Receive SMS notifications for important updates</label>
                            </div>

                            <button type="submit" name="update_notifications" class="button">
                                <i data-lucide="settings" class="inline w-4 h-4 mr-2"></i>Update Preferences
                            </button>
                        </form>
                    </div>

                    <!-- Account Information -->
                    <div class="settings-section">
                        <h3><i data-lucide="info" class="inline w-5 h-5 mr-2"></i>Account Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Username</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Library ID</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['library_id'] ?? 'LIB-' . str_pad($user_id, 6, '0', STR_PAD_LEFT)); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Account Status</div>
                                <div class="info-value">
                                    <span class="badge <?php echo ($user['is_active'] ?? 1) ? 'success' : 'danger'; ?>">
                                        <?php echo ($user['is_active'] ?? 1) ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Member Since</div>
                                <div class="info-value"><?php echo htmlspecialchars(date('M d, Y', strtotime($user['created_at'] ?? 'now'))); ?></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
    <script>
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>