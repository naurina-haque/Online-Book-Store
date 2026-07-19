<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];

function tableExists(mysqli $conn, string $tableName): bool
{
    $stmt = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $tableName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result && mysqli_num_rows($result) > 0;
}

function fetchCustomerPendingOrders(mysqli $conn, int $userId): array
{
    if (!tableExists($conn, 'orders')) {
        return [];
    }

    $sql = "SELECT id, order_number, delivery_address, items, total_amount, status, created_at
            FROM orders
            WHERE customer_id = ? AND status = 'Pending'
            ORDER BY created_at ASC";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

$dbConnected = tableExists($conn, 'orders');
$orders = $dbConnected ? fetchCustomerPendingOrders($conn, $userId) : [];
$pendingCount = count($orders);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Track Orders · Online Book Store</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="track_order.css">
</head>
<body>
<div class="dash-layout">

    <div class="dash-sidebar">
        <div class="dash-brand">Book Store</div>
        <nav class="dash-nav">
            <a href="customerdashboard.php">Browse Books</a>
            <a href="order_history.php">My Orders</a>
            <a href="track_order.php" class="active">Track Orders</a>
            
        </nav>
        <a href="logout.php" class="dash-logout">Log out</a>
    </div>

    <div class="dash-main">
        <div class="page-header">
            <div>
                <span class="eyebrow">My Orders</span>
                <h2>Track Orders</h2>
            </div>
            <span class="track-pill"><?= $pendingCount ?> pending</span>
        </div>

        <?php if (!$dbConnected): ?>
            <section class="empty">
                <h2>Orders table not found</h2>
                <p>Create an <strong>orders</strong> table in the bookstore database to start tracking.</p>
            </section>
        <?php elseif (!$orders): ?>
            <section class="empty">
                <div class="empty-icon">✓</div>
                <h2>Nothing in transit</h2>
                <p>You have no pending orders right now. New orders will appear here until they are accepted.</p>
            </section>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Items</th>
                            <th>Delivery Address</th>
                            <th>Placed</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order):
                            $orderNumber = $order['order_number'] ?? $order['id'] ?? 'N/A';
                        ?>
                            <tr>
                                <td>
                                    <span class="order-number">#<?= htmlspecialchars((string) $orderNumber) ?></span>
                                </td>

                                <td class="items-cell">
                                    <?= htmlspecialchars((string) ($order['items'] ?? '—')) ?>
                                </td>

                                <td class="items-cell">
                                    📍 <?= htmlspecialchars((string) ($order['delivery_address'] ?? 'No Address Listed')) ?>
                                </td>

                                <td class="date">
                                    <strong><?= htmlspecialchars(date('d M Y', strtotime((string) ($order['created_at'] ?? 'now')))) ?></strong>
                                    <span class="time-stamp"><?= htmlspecialchars(date('h:i A', strtotime((string) ($order['created_at'] ?? 'now')))) ?></span>
                                </td>

                                <td class="amount">
                                    ৳<?= htmlspecialchars(number_format((float) ($order['total_amount'] ?? 0), 2)) ?>
                                </td>

                                <td>
                                    <span class="status pending">Pending</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
