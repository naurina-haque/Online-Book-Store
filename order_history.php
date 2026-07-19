<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}

$user_id = (int) $_SESSION['user_id'];
$orders = [];

$sql = "SELECT * FROM orders WHERE customer_id = ? AND status = 'Delivered' ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result) {
        $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Order History | Premium Books</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/orders.css">
</head>
<body>
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>
    <div class="bg-shape shape-3"></div>

    <main class="container">
        <nav class="top-nav">
            <a class="back-link" href="customerdashboard.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                Back to Shop
            </a>
        </nav>
        
        <header class="page-header">
            <h1>My Order History</h1>
            <p>Track your past purchases and their status</p>
        </header>

        <?php if (!$orders): ?>
            <section class="empty-state glass-card">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                </div>
                <h2>No Orders Yet</h2>
                <p>You haven't made any purchases. Your completed orders will magically appear here.</p>
                <a href="customerdashboard.php" class="btn-primary">Browse Books</a>
            </section>
        <?php else: ?>
            <div class="table-container glass-card">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Items</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="col-id">#<?= e($order['order_number']) ?></td>
                                <td class="col-items"><?= e($order['items'] ?: 'No items recorded') ?></td>
                                <td class="col-date">
                                    <span class="date-main"><?= e(date('d M Y', strtotime($order['created_at']))) ?></span>
                                    <span class="date-sub"><?= e(date('h:i A', strtotime($order['created_at']))) ?></span>
                                </td>
                                <td class="col-total">৳<?= e(number_format((float) $order['total_amount'], 2)) ?></td>
                                <td class="col-status">
                                    <span class="status-badge <?= strtolower(e($order['status'])) ?>">
                                        <?= e($order['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
