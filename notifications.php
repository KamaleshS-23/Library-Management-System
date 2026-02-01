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
$notifications = [];
$errors = [];

try {
    // Check if notifications table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($table_check->rowCount() == 0) {
        throw new Exception('Notifications system not configured yet.');
    }

    // Get user's notifications
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark all as read
    $update_stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = :user_id AND is_read = 0
    ");
    $update_stmt->bindParam(':user_id', $user_id);
    $update_stmt->execute();

} catch (PDOException $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $notification_id = $_POST['notification_id'];
    
    try {
        if ($_POST['action'] === 'delete') {
            $delete_stmt = $pdo->prepare("
                DELETE FROM notifications 
                WHERE id = :id AND user_id = :user_id
            ");
            $delete_stmt->bindParam(':id', $notification_id);
            $delete_stmt->bindParam(':user_id', $user_id);
            $delete_stmt->execute();
            
            // Refresh the page
            header("Location: notifications.php");
            exit();
        }
    } catch (PDOException $e) {
        $errors[] = 'Action failed: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css">
  <title>My Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .notification-card {
            transition: all 0.2s ease;
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .unread {
            background-color: #EFF6FF;
            border-left: 4px solid #3B82F6;
        }
        .notification-type {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 500;
        }
        .type-info {
            background-color: #EFF6FF;
            color: #1E40AF;
        }
        .type-warning {
            background-color: #FEF3C7;
            color: #92400E;
        }
        .type-urgent {
            background-color: #FEE2E2;
            color: #B91C1C;
        }
    </style>
</head>
<body>
  <div class="dashboard">
    <!-- Sidebar -->
    <?php include('sidebar.php'); ?>
    
    <!-- Main Content -->
    <main class="main-content">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-indigo-600 text-white p-6">
            <div class="max-w-6xl mx-auto">
                <h1 class="text-2xl font-bold">My Notifications</h1>
                <p class="text-indigo-100">Your recent library notifications</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-6xl mx-auto px-4 py-6">
            <!-- Display errors if any -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Notifications List -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                    <h2 class="text-lg font-semibold flex items-center">
                        <i data-lucide="bell" class="mr-2"></i>
                        Recent Notifications
                    </h2>
                    <form method="POST" action="notifications.php">
                        <button type="submit" name="mark_all_read" 
                                class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center">
                            <i data-lucide="check-circle" class="w-4 h-4 mr-1"></i>
                            Mark all as read
                        </button>
                    </form>
                </div>

                <?php if (empty($notifications)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i data-lucide="bell-off" class="mx-auto w-12 h-12"></i>
                        <p class="mt-2">No notifications found.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($notifications as $notification): 
                            $type_class = '';
                            switch ($notification['type']) {
                                case 'warning': $type_class = 'type-warning'; break;
                                case 'urgent': $type_class = 'type-urgent'; break;
                                default: $type_class = 'type-info';
                            }
                        ?>
                            <div class="notification-card p-6 <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <span class="notification-type <?php echo $type_class; ?> mr-2">
                                                <?php echo ucfirst($notification['type']); ?>
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                <?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?>
                                            </span>
                                        </div>
                                        <p class="text-gray-800">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        <?php if (!empty($notification['action_url'])): ?>
                                            <div class="mt-3">
                                                <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" 
                                                   class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
                                                    <i data-lucide="arrow-right-circle" class="w-4 h-4 mr-1"></i>
                                                    <?php echo htmlspecialchars($notification['action_text'] ?? 'View details'); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <form method="POST">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" name="action" value="delete" 
                                                class="text-gray-400 hover:text-gray-600 ml-4">
                                            <i data-lucide="trash-2" class="w-5 h-5"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
    </script>
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