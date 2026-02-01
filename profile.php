<?php
// Include the config.php file
require_once('config.php');

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'You must be logged in to view this page.']));
}

$user_id = $_SESSION['user_id'];

try {
    // Query to fetch the user details
    $sql = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch the user details from the database
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die(json_encode(['error' => 'User not found.']));
    }

    // Set default values for new fields if they don't exist
    $user['profile_pic'] = $user['profile_pic'] ?? null;
    $user['mobile'] = $user['mobile'] ?? '';
    $user['library_id'] = $user['library_id'] ?? 'LIB-' . str_pad($user['id'], 6, '0', STR_PAD_LEFT);
    $user['membership_type'] = $user['membership_type'] ?? '';
    $user['membership_date'] = $user['membership_date'] ?? date('Y-m-d');
    $user['last_login'] = $user['last_login'] ?? 'Never';

    // Initialize counts with default values
    $user['borrowed_books_count'] = 0;
    $user['reserved_books_count'] = 0;
    $user['total_borrowed'] = 0;
    $user['fines'] = '0.00';

    // Try to get counts for user activity (with error handling)
    try {
        // Check if borrowed_books table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'borrowings'")->fetch();
        if ($table_check) {
            $borrowed_count = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE user_id = $user_id AND status = 'borrowed'")->fetchColumn();
            $user['borrowed_books_count'] = $borrowed_count;
            
            $total_borrowed = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE user_id = $user_id")->fetchColumn();
            $user['total_borrowed'] = $total_borrowed;
        }
        
        // Check if reservations table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'reservations'")->fetch();
        if ($table_check) {
            $reservation_count = $pdo->query("SELECT COUNT(*) FROM reservations WHERE user_id = $user_id AND (status = 'pending' OR status = 'active')")->fetchColumn();
            $user['reserved_books_count'] = $reservation_count;
        }
        
        // Check if fines table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'fines'")->fetch();
        if ($table_check) {
            $fines = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM fines WHERE user_id = $user_id AND status = 'unpaid'")->fetchColumn();
            $user['fines'] = number_format($fines, 2);
        }
    } catch (PDOException $e) {
        // Silently continue with default values if these tables don't exist
        error_log("Database tables not found: " . $e->getMessage());
    }

} catch (PDOException $e) {
    die(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
}

// AJAX request handler - processing form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        // Initialize response array
        $response = [];
        
        // Handle profile picture upload if provided
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/profile_pics/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception('Failed to create upload directory');
                }
            }

            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedType = finfo_file($fileInfo, $_FILES['profile_pic']['tmp_name']);
            finfo_close($fileInfo);
            
            if (!in_array($detectedType, $allowedTypes)) {
                throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed');
            }

            // Generate unique filename
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $user_id . '_' . uniqid() . '.' . strtolower($ext);
            $targetPath = $uploadDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetPath)) {
                // Delete old profile picture if exists and not the same as the new one
                if (!empty($user['profile_pic']) && file_exists($uploadDir . $user['profile_pic']) && $user['profile_pic'] !== $filename) {
                    @unlink($uploadDir . $user['profile_pic']);
                }
                
                // Update profile picture in database
                $updatePicSql = "UPDATE users SET profile_pic = :filename WHERE id = :user_id";
                $updatePicStmt = $pdo->prepare($updatePicSql);
                $updatePicStmt->bindParam(':filename', $filename, PDO::PARAM_STR);
                $updatePicStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                
                if (!$updatePicStmt->execute()) {
                    throw new Exception('Failed to update profile picture in database');
                }
                
                $user['profile_pic'] = $filename;
                $response['profile_pic'] = $filename;
                $response['photo_success'] = 'Profile picture updated successfully!';
            } else {
                throw new Exception('Failed to move uploaded file');
            }
        }
        
        // Handle other profile updates
        $name = trim($_POST['name'] ?? $user['full_name']);
        $mobile = trim($_POST['mobile'] ?? '');
        $email = trim($_POST['email'] ?? $user['email']);
        $membership_type = $_POST['membership_type'] ?? '';
        
        // Validate inputs
        if (empty($name)) {
            throw new Exception('Full name is required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        if (!empty($mobile) && !preg_match('/^[0-9]{10,15}$/', $mobile)) {
            throw new Exception('Mobile number must be 10-15 digits');
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        $updateSql = "UPDATE users SET 
            full_name = :name,
            mobile = :mobile,
            email = :email,
            membership_type = :membership_type
            WHERE id = :user_id";
            
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->bindParam(':name', $name);
        $updateStmt->bindParam(':mobile', $mobile);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->bindParam(':membership_type', $membership_type);
        $updateStmt->bindParam(':user_id', $user_id);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update profile information');
        }
        
        // Update local user data
        $user['full_name'] = $name;
        $user['mobile'] = $mobile;
        $user['email'] = $email;
        $user['membership_type'] = $membership_type;
        
        // Commit transaction
        $pdo->commit();
        
        $response['success'] = 'Profile updated successfully!';
        $response['name'] = $name;
        $response['email'] = $email;
        $response['mobile'] = $mobile;
        $response['membership_type'] = $membership_type;
        
        echo json_encode($response);
        exit;
        
    } catch (Exception $e) {
        // Roll back transaction if there was an error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Profile update failed: " . $e->getMessage());
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.5.95/css/materialdesignicons.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1.5rem;
            border: 3px solid #4361ee;
        }
        .profile-info h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: #1e293b;
        }
        .profile-info p {
            color: #64748b;
            margin: 0.25rem 0;
        }
        .badge {
            display: inline-block;
            background: #4361ee;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        .info-item {
            margin-bottom: 0.5rem;
        }
        .info-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        .info-value {
            font-weight: 500;
            color: #1e293b;
        }
        .info-value.empty {
            color: #94a3b8;
            font-style: italic;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-control {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            transition: border-color 0.15s ease-in-out;
        }
        .form-control:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.5);
        }
        .file-upload {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 2rem;
            border: 2px dashed #e2e8f0;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .file-upload:hover {
            border-color: #4361ee;
            background-color: #f8fafc;
        }
        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            cursor: pointer;
        }
        .file-upload-text {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.5rem;
        }
        .file-upload-input {
            position: absolute;
            width: 0;
            height: 0;
            opacity: 0;
            overflow: hidden;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #4361ee;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background-color: #3a56d4;
        }
        .btn-block {
            width: 100%;
        }
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem;
            background-color: #10b981;
            color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transform: translateY(-100px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        .toast.error {
            background-color: #ef4444;
        }
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div id="toast" class="toast hidden"></div>
    <div class="dashboard">
        <!-- Sidebar -->
        <?php include('sidebar.php'); ?>
        
        <!-- Main Content -->
        <main class="main-content">
        <div class="card">
            <div class="profile-header">
                <img src="<?php echo !empty($user['profile_pic']) ? '../uploads/profile_pics/' . htmlspecialchars($user['profile_pic']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name']) . '&background=4361ee&color=fff&size=200'; ?>" 
                    alt="Profile Picture" class="profile-picture" id="profile-picture">
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <?php if (!empty($user['membership_type'])): ?>
                        <span class="badge"><?php echo htmlspecialchars($user['membership_type']); ?> Member</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="section-title">Personal Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Mobile</div>
                    <div class="info-value <?php echo empty($user['mobile']) ? 'empty' : ''; ?>">
                        <?php echo !empty($user['mobile']) ? htmlspecialchars($user['mobile']) : 'Not provided'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <br>
                <div class="info-item">
                    <div class="info-label">Library ID</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['library_id']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Membership Date</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['membership_date']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Membership Type</div>
                    <div class="info-value <?php echo empty($user['membership_type']) ? 'empty' : ''; ?>">
                        <?php echo !empty($user['membership_type']) ? htmlspecialchars($user['membership_type']) : 'Not selected'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h2 class="section-title">Update Profile</h2>
            <form id="profile-form" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="mobile">Mobile Number</label>
                    <input type="text" id="mobile" name="mobile" class="form-control" value="<?php echo htmlspecialchars($user['mobile']); ?>" placeholder="Enter your mobile number">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="membership_type">Membership Type</label>
                    <select id="membership_type" name="membership_type" class="form-control">
                        <option value="">Select membership type</option>
                        <option value="Basic" <?php echo $user['membership_type'] === 'Basic' ? 'selected' : ''; ?>>Basic</option>
                        <option value="Premium" <?php echo $user['membership_type'] === 'Premium' ? 'selected' : ''; ?>>Premium</option>
                        <option value="Student" <?php echo $user['membership_type'] === 'Student' ? 'selected' : ''; ?>>Student</option>
                        <option value="Faculty" <?php echo $user['membership_type'] === 'Faculty' ? 'selected' : ''; ?>>Faculty</option>
                    </select>
                </div>
        
                <div class="form-group">
                    <label for="profile_pic">Profile Picture</label>
                    <div class="file-upload">
                        <label for="profile_pic" class="file-upload-label">
                            <i data-lucide="upload" style="width: 24px; height: 24px;"></i>
                            <div>Click to upload new profile picture</div>
                            <span class="file-upload-text">JPG, PNG, GIF, or WebP images supported</span>
                        </label>
                        <input type="file" id="profile_pic" name="profile_pic" class="file-upload-input" accept="image/*">
                    </div>
                    <div id="photo-feedback" class="text-sm mt-1"></div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" id="submit-button">
                    <i data-lucide="save" style="width: 16px; height: 16px; margin-right: 8px;"></i> Save Profile
                </button>
            </form>
        </div>

        <div class="card">
            <h2 class="section-title">Library Account</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Currently Borrowed</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['borrowed_books_count']); ?> books</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Reserved Books</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['reserved_books_count']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fines</div>
                    <div class="info-value">$<?php echo htmlspecialchars($user['fines']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total Borrowed</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['total_borrowed']); ?> books</div>
                </div>
            </div>
        </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        
        // Show toast notification
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = isError ? 'toast error' : 'toast';
            toast.classList.remove('hidden');
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.classList.add('hidden'), 300);
            }, 5000);
        }
        
        // Handle profile form submission via AJAX
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = document.getElementById('submit-button');
            const originalButtonText = submitButton.innerHTML;
            
            // Show loading state
            submitButton.disabled = true;
            submitButton.innerHTML = '<i data-lucide="loader-2" class="loading-spinner" style="width: 16px; height: 16px; margin-right: 8px;"></i> Saving...';
            lucide.createIcons();
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    showToast(data.error, true);
                    
                    // Show photo-specific feedback if available
                    if (data.error.includes('picture') || data.error.includes('image') || data.error.includes('file')) {
                        document.getElementById('photo-feedback').textContent = data.error;
                        document.getElementById('photo-feedback').className = 'text-sm mt-1 text-red-500';
                    }
                } else {
                    // Show success message
                    if (data.photo_success) {
                        showToast(data.photo_success);
                        document.getElementById('photo-feedback').textContent = data.photo_success;
                        document.getElementById('photo-feedback').className = 'text-sm mt-1 text-green-500';
                    }
                    
                    if (data.success) {
                        showToast(data.success);
                    }
                    
                    // Update profile picture if changed
                    if (data.profile_pic) {
                        const profilePic = document.getElementById('profile-picture');
                        profilePic.src = '../uploads/profile_pics/' + data.profile_pic + '?' + new Date().getTime();
                    }
                    
                    // Update displayed profile information
                    if (data.name) {
                        document.querySelector('.profile-info h1').textContent = data.name;
                        document.querySelectorAll('.info-value')[0].textContent = data.name;
                    }
                    if (data.email) {
                        document.querySelector('.profile-info p').textContent = data.email;
                        document.querySelectorAll('.info-value')[2].textContent = data.email;
                    }
                    if (data.mobile) {
                        const mobileValue = data.mobile || 'Not provided';
                        const mobileElement = document.querySelectorAll('.info-value')[1];
                        mobileElement.textContent = mobileValue;
                        mobileElement.className = data.mobile ? 'info-value' : 'info-value empty';
                    }
                    if (data.membership_type !== undefined) {
                        const membershipValue = data.membership_type || 'Not selected';
                        const membershipElement = document.querySelectorAll('.info-value')[5];
                        membershipElement.textContent = membershipValue;
                        membershipElement.className = data.membership_type ? 'info-value' : 'info-value empty';
                        
                        // Update badge
                        const badge = document.querySelector('.badge');
                        if (data.membership_type) {
                            if (!badge) {
                                const profileInfo = document.querySelector('.profile-info');
                                const newBadge = document.createElement('span');
                                newBadge.className = 'badge';
                                newBadge.textContent = data.membership_type + ' Member';
                                profileInfo.appendChild(newBadge);
                            } else {
                                badge.textContent = data.membership_type + ' Member';
                            }
                        } else if (badge) {
                            badge.remove();
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while updating profile. Please try again.', true);
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                lucide.createIcons();
            });
        });
        
        // Preview profile picture before upload
        document.getElementById('profile_pic').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const feedbackEl = document.getElementById('photo-feedback');
            
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    feedbackEl.textContent = 'Only JPG, PNG, GIF, and WebP images are allowed';
                    feedbackEl.className = 'text-sm mt-1 text-red-500';
                    e.target.value = '';
                    return;
                }
                
                // Clear any previous feedback
                feedbackEl.textContent = '';
                feedbackEl.className = 'text-sm mt-1';
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profile-picture').src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    });
    </script>
        </main>
    </div>
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>
</html>