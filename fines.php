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
$fines = [];
$total_fines = 0;
$errors = [];

try {
    // Check if fines table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'fines'");
    if ($table_check->rowCount() == 0) {
        throw new Exception('Fines system not configured yet. Please check back later.');
    }

    // Get user's fines
    $stmt = $pdo->prepare("
        SELECT f.*, b.title as book_title 
        FROM fines f
        LEFT JOIN borrowings br ON f.borrowing_id = br.id
        LEFT JOIN books b ON br.book_id = b.id
        WHERE f.user_id = :user_id AND f.status = 'unpaid'
        ORDER BY f.issued_date DESC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $fines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total fines
    $total_stmt = $pdo->prepare("
        SELECT SUM(amount) as total 
        FROM fines 
        WHERE user_id = :user_id AND status = 'unpaid'
    ");
    $total_stmt->bindParam(':user_id', $user_id);
    $total_stmt->execute();
    $total = $total_stmt->fetch(PDO::FETCH_ASSOC);
    $total_fines = $total['total'] ?? 0;

} catch (PDOException $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    $errors[] = $e->getMessage();
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_fine'])) {
    $fine_id = $_POST['fine_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Update fine status
        $update_stmt = $pdo->prepare("
            UPDATE fines 
            SET status = 'paid', 
                payment_date = NOW() 
            WHERE id = :fine_id AND user_id = :user_id
        ");
        $update_stmt->bindParam(':fine_id', $fine_id);
        $update_stmt->bindParam(':user_id', $user_id);
        $update_stmt->execute();
        
        // Record payment transaction
        $payment_stmt = $pdo->prepare("
            INSERT INTO payments (user_id, fine_id, amount, payment_method, transaction_date)
            VALUES (:user_id, :fine_id, 
                   (SELECT amount FROM fines WHERE id = :fine_id), 
                   'online', NOW())
        ");
        $payment_stmt->bindParam(':user_id', $user_id);
        $payment_stmt->bindParam(':fine_id', $fine_id);
        $payment_stmt->execute();
        
        $pdo->commit();
        
        // Refresh the page to show updated fines
        header("Location: fines.php");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = 'Payment failed: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css">
  <title>My Fines</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .fine-card {
            transition: all 0.2s ease;
        }
        .fine-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .status-unpaid {
            background-color: #FEE2E2;
            color: #B91C1C;
        }
        .status-paid {
            background-color: #D1FAE5;
            color: #065F46;
        }
        .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 500;
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
                <h1 class="text-2xl font-bold">My Fines</h1>
                <p class="text-indigo-100">View and pay your outstanding fines</p>
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

            <!-- Summary Card -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="mb-4 md:mb-0">
                        <h2 class="text-lg font-semibold flex items-center">
                            <i data-lucide="alert-circle" class="mr-2"></i>
                            Outstanding Balance
                        </h2>
                        <p class="text-gray-600">Total unpaid fines</p>
                    </div>
                    <div class="text-3xl font-bold text-red-600">
                        $<?php echo number_format($total_fines, 2); ?>
                    </div>
                </div>
            </div>

            <!-- Fines List -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h2 class="text-lg font-semibold flex items-center">
                        <i data-lucide="list" class="mr-2"></i>
                        Fine Details
                    </h2>
                </div>

                <?php if (empty($fines)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i data-lucide="check-circle" class="mx-auto w-12 h-12"></i>
                        <p class="mt-2">You don't have any outstanding fines.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($fines as $fine): ?>
                            <div class="fine-card p-6">
                                <div class="flex flex-col md:flex-row justify-between">
                                    <div class="mb-4 md:mb-0">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <h3 class="font-medium">
                                                    <?php echo htmlspecialchars($fine['book_title'] ?? 'General Fine'); ?>
                                                </h3>
                                                <p class="text-sm text-gray-600">
                                                    Issued: <?php echo date('M j, Y', strtotime($fine['issued_date'])); ?>
                                                </p>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    Reason: <?php echo htmlspecialchars($fine['reason']); ?>
                                                </p>
                                            </div>
                                            <span class="badge status-unpaid ml-4">Unpaid</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col md:items-end">
                                        <div class="text-xl font-semibold text-red-600 mb-2">
                                            $<?php echo number_format($fine['amount'], 2); ?>
                                        </div>
                                        <form method="POST" class="w-full md:w-auto">
                                            <input type="hidden" name="fine_id" value="<?php echo $fine['id']; ?>">
                                            <button type="submit" name="pay_fine" 
                                                    class="w-full md:w-auto px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md flex items-center justify-center">
                                                <i data-lucide="credit-card" class="w-4 h-4 mr-2"></i>
                                                Pay Now
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payment History -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden mt-8">
                <div class="px-6 py-4 bg-gray-50 border-b">
                    <h2 class="text-lg font-semibold flex items-center">
                        <i data-lucide="history" class="mr-2"></i>
                        Payment History
                    </h2>
                </div>

                <?php
                try {
                    $history_stmt = $pdo->prepare("
                        SELECT p.*, f.reason 
                        FROM payments p
                        JOIN fines f ON p.fine_id = f.id
                        WHERE p.user_id = :user_id
                        ORDER BY p.transaction_date DESC
                        LIMIT 10
                    ");
                    $history_stmt->bindParam(':user_id', $user_id);
                    $history_stmt->execute();
                    $payments = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $payments = [];
                    $errors[] = 'Error loading payment history: ' . $e->getMessage();
                }
                ?>

                <?php if (empty($payments)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i data-lucide="dollar-sign" class="mx-auto w-12 h-12"></i>
                        <p class="mt-2">No payment history found.</p>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($payments as $payment): ?>
                            <div class="p-6">
                                <div class="flex justify-between">
                                    <div>
                                        <p class="font-medium">
                                            <?php echo htmlspecialchars($payment['reason']); ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <?php echo date('M j, Y h:i A', strtotime($payment['transaction_date'])); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-green-600 font-semibold">
                                            -$<?php echo number_format($payment['amount'], 2); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?php echo ucfirst($payment['payment_method']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add this to fines.php before closing api tag
        document.addEventListener('DOMContentLoaded', () => {
            // Handle fine payments with AJAX
            document.querySelectorAll('button[name="pay_fine"]').forEach(button => {
                button.addEventListener('click', async function(e) {
                    e.preventDefault();
                    
                    const form = this.closest('form');
                    const fineId = form.querySelector('input[name="fine_id"]').value;
                    const fineCard = form.closest('.fine-card');
                    
                    try {
                        button.disabled = true;
                        button.innerHTML = '<i data-lucide="loader" class="animate-spin w-4 h-4 mr-2"></i> Processing...';
                        lucide.createIcons();
                        
                        const result = await makeRequest('api/pay_fine.php', 'POST', {
                            fine_id: fineId
                        });
                        
                        if (result.success) {
                            // Update UI
                            fineCard.querySelector('.badge').className = 'badge status-paid';
                            fineCard.querySelector('.badge').textContent = 'Paid';
                            fineCard.querySelector('button[name="pay_fine"]').remove();
                            
                            // Update total
                            const totalElement = document.querySelector('.text-3xl.font-bold');
                            const currentTotal = parseFloat(totalElement.textContent.replace('$', ''));
                            const paidAmount = parseFloat(result.amount);
                            totalElement.textContent = '$' + (currentTotal - paidAmount).toFixed(2);
                            
                            // Add to payment history
                            addPaymentToHistory(result.payment);
                        } else {
                            alert('Payment failed: ' + (result.error || 'Unknown error'));
                            button.disabled = false;
                            button.innerHTML = '<i data-lucide="credit-card" class="w-4 h-4 mr-2"></i> Pay Now';
                            lucide.createIcons();
                        }
                    } catch (error) {
                        console.error(error);
                        button.disabled = false;
                        button.innerHTML = '<i data-lucide="credit-card" class="w-4 h-4 mr-2"></i> Pay Now';
                        lucide.createIcons();
                    }
                });
            });
            
            function addPaymentToHistory(payment) {
                const historySection = document.querySelector('.divide-y.divide-gray-200:last-child');
                const newPayment = document.createElement('div');
                newPayment.className = 'p-6';
                newPayment.innerHTML = `
                    <div class="flex justify-between">
                        <div>
                            <p class="font-medium">${payment.reason}</p>
                            <p class="text-sm text-gray-600">
                                ${new Date(payment.transaction_date).toLocaleString()}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-green-600 font-semibold">
                                -$${parseFloat(payment.amount).toFixed(2)}
                            </p>
                            <p class="text-xs text-gray-500">
                                ${payment.payment_method}
                            </p>
                        </div>
                    </div>
                `;
                historySection.prepend(newPayment);
            }
        });
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