<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function tableExists(mysqli $conn, string $tableName): bool
{
    $stmt = mysqli_prepare($conn, 'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 's', $tableName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result && mysqli_num_rows($result) > 0;
}

function tableColumns(mysqli $conn, string $tableName): array
{
    $columns = [];
    $stmt = mysqli_prepare($conn, 'SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?');
    mysqli_stmt_bind_param($stmt, 's', $tableName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['COLUMN_NAME'];
    }

    return $columns;
}

function firstExisting(array $candidates, array $columns): ?string
{
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

function fetchOrders(mysqli $conn, ?string $status = null): array
{
    if (!tableExists($conn, 'orders')) {
        throw new RuntimeException('orders table missing');
    }

    $columns = tableColumns($conn, 'orders');
    $idColumn = firstExisting(['id', 'order_id'], $columns);
    $statusColumn = firstExisting(['status', 'order_status'], $columns);
    $createdAtColumn = firstExisting(['created_at', 'placed_at', 'ordered_at'], $columns);
    $customerIdColumn = firstExisting(['customer_id', 'user_id'], $columns);
    $customerNameColumn = firstExisting(['customer_name', 'name'], $columns);
    $deliveryAddressColumn = firstExisting(['delivery_address', 'address', 'shipping_address'], $columns);
    $totalAmountColumn = firstExisting(['total_amount', 'amount', 'grand_total', 'total'], $columns);
    $orderNumberColumn = firstExisting(['order_number', 'order_no', 'invoice_no'], $columns);

    if (!$idColumn || !$statusColumn || !$createdAtColumn || !$totalAmountColumn) {
        throw new RuntimeException('orders table structure is incomplete');
    }

    $selectParts = [
        'o.' . $idColumn . ' AS id',
        ($orderNumberColumn ? 'o.' . $orderNumberColumn : 'o.' . $idColumn) . ' AS order_number',
        ($customerIdColumn ? 'o.' . $customerIdColumn : 'NULL') . ' AS customer_id',
        ($customerNameColumn ? 'o.' . $customerNameColumn : 'u.name') . ' AS customer_name',
        ($deliveryAddressColumn ? 'o.' . $deliveryAddressColumn : 'NULL') . ' AS delivery_address',
        'o.' . $totalAmountColumn . ' AS total_amount',
        'o.' . $statusColumn . ' AS status',
        'o.' . $createdAtColumn . ' AS created_at',
    ];

    $sql = 'SELECT ' . implode(', ', $selectParts) . ' FROM orders o';

    if ($customerIdColumn && tableExists($conn, 'users')) {
        $sql .= ' LEFT JOIN users u ON u.user_id = o.' . $customerIdColumn;
    }

    if ($status !== null && $status !== '') {
        $sql .= ' WHERE o.' . $statusColumn . ' = ?';
    }

    $sql .= ' ORDER BY o.' . $createdAtColumn . ' DESC';

    $stmt = mysqli_prepare($conn, $sql);
    if ($status !== null && $status !== '') {
        mysqli_stmt_bind_param($stmt, 's', $status);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

function updateOrderStatus(mysqli $conn, int $orderId, string $newStatus): bool
{
    if (!tableExists($conn, 'orders')) {
        return false;
    }

    $columns = tableColumns($conn, 'orders');
    $idColumn = firstExisting(['id', 'order_id'], $columns);
    $statusColumn = firstExisting(['status', 'order_status'], $columns);

    if (!$idColumn || !$statusColumn) {
        return false;
    }

    $sql = 'UPDATE orders SET ' . $statusColumn . ' = ? WHERE ' . $idColumn . ' = ?';
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'si', $newStatus, $orderId);

    return mysqli_stmt_execute($stmt);
}

$allowedStatuses = ['Pending', 'Delivered'];
$status = $_GET['status'] ?? '';
if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            $error = 'Invalid order selection.';
        } elseif (updateOrderStatus($conn, $orderId, 'Delivered')) {
            $success = 'Order marked as fulfilled.';
        } else {
            $error = 'Could not update the order status.';
        }
    }
}

$dbConnected = tableExists($conn, 'orders');
$orders = [];
$allOrdersForStats = [];

try {
    if ($dbConnected) {
        $orders = fetchOrders($conn, $status ?: null);
        $allOrdersForStats = fetchOrders($conn);
    }
} catch (Throwable $e) {
    $orders = [];
    $allOrdersForStats = [];
    $dbConnected = false;
    if ($error === '') {
        $error = 'Orders are not ready yet. Please create the orders table first.';
    }
}

$pendingCount = count(array_filter($allOrdersForStats, fn ($o) => strtolower((string) ($o['status'] ?? '')) === 'pending'));
$deliveredCount = count(array_filter($allOrdersForStats, fn ($o) => strtolower((string) ($o['status'] ?? '')) === 'delivered'));
$baseUrl = '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Orders · Online Book Store</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .orders-layout { max-width: 1180px; margin: 0 auto; padding: 32px 24px 56px; }
        .orders-topbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 28px; }
        .orders-brand { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; color: var(--ink); font-weight: 700; font-family: var(--font-display); font-size: 20px; }
        .brand-mark { width: 36px; height: 36px; border-radius: 10px; background: var(--navy); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-family: var(--font-mono); }
        .user-chip { display: inline-flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1px solid var(--rule); border-radius: 999px; background: var(--paper-raised); color: var(--ink-soft); font-weight: 600; }
        .avatar { width: 30px; height: 30px; border-radius: 999px; background: var(--brass-soft); color: var(--ink); display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; }
        .hero { display: flex; justify-content: space-between; align-items: end; gap: 18px; margin-bottom: 26px; }
        .hero h1 { font-family: var(--font-display); font-size: clamp(30px, 4vw, 48px); line-height: 1; color: var(--ink); margin-top: 8px; }
        .eyebrow { text-transform: uppercase; letter-spacing: .12em; font-size: 12px; font-weight: 700; color: var(--brass); }
        .subheading { margin-top: 10px; color: var(--ink-soft); max-width: 60ch; line-height: 1.6; }
        .stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin: 28px 0; }
        .stat { background: var(--paper-raised); border: 1px solid var(--rule); border-radius: 18px; padding: 20px; }
        .stat.amber { background: var(--brass-soft); }
        .stat.mint { background: var(--sage-bg); }
        .stat-label { display: block; color: var(--ink-soft); font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .stat-value { font-family: var(--font-display); font-size: 34px; color: var(--ink); }
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin: 24px 0 18px; padding: 16px 18px; border: 1px solid var(--rule); border-radius: 16px; background: var(--paper-raised); }
        .toolbar-copy { color: var(--ink-soft); }
        .filters { display: inline-flex; flex-wrap: wrap; gap: 8px; }
        .filters a { text-decoration: none; color: var(--ink); border: 1px solid var(--rule); background: var(--paper); padding: 8px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; }
        .filters a.active { background: var(--navy); color: #fff; border-color: var(--navy); }
        .notice { margin: 18px 0; padding: 12px 14px; border-radius: 10px; font-weight: 600; }
        .notice.success { background: #ecfdf5; color: #065f46; }
        .notice.error { background: #fef2f2; color: #991b1b; }
        .table-wrap { overflow-x: auto; background: var(--paper-raised); border: 1px solid var(--rule); border-radius: 18px; }
        table { width: 100%; border-collapse: collapse; min-width: 860px; }
        th, td { padding: 16px 18px; border-bottom: 1px solid var(--rule); text-align: left; vertical-align: top; }
        th { font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: var(--ink-soft); background: #fcf8f0; }
        tbody tr:hover { background: #fcf8f0; }
        .customer { display: flex; gap: 12px; align-items: flex-start; }
        .customer strong { display: block; color: var(--ink); }
        .customer small { display: block; }
        .order-number { font-family: var(--font-mono); font-size: 13px; color: var(--navy); font-weight: 600; }
        .date strong { display: block; color: var(--ink); }
        .time-stamp { font-size: 12px; color: var(--ink-soft); }
        .amount { font-family: var(--font-mono); font-weight: 700; color: var(--ink); }
        .status { display: inline-flex; padding: 8px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; }
        .status.pending { background: #fff7ed; color: #9a3412; }
        .status.delivered { background: #ecfdf5; color: #065f46; }
        .action-form { margin: 0; display: inline-block; }
        .action-btn { cursor: pointer; border: none; border-radius: 999px; padding: 10px 14px; background: var(--navy); color: #fff; font: inherit; font-weight: 600; }
        .action-btn:hover { background: var(--navy-soft); }
        .done { color: var(--sage); font-weight: 700; }
        .empty { text-align: center; padding: 56px 24px; border: 1px dashed var(--rule); border-radius: 18px; background: var(--paper-raised); }
        .empty h2 { font-family: var(--font-display); font-size: 28px; color: var(--ink); margin-bottom: 10px; }
        .empty p { color: var(--ink-soft); }
        .empty-icon { font-size: 36px; margin-bottom: 12px; }
        @media (max-width: 900px) {
            .hero, .toolbar, .orders-topbar { align-items: flex-start; flex-direction: column; }
            .stats { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<main class="orders-layout">
    <header class="orders-topbar">
        <a class="orders-brand" href="admindashboard.php">
            <span class="brand-mark">B</span>Book Store
        </a>
        <a href="admindashboard.php" style="text-decoration: none;">
            <span class="user-chip">
                <span class="avatar">A</span>Admin desk
            </span>
        </a>
    </header>

    <section class="hero">
        <div>
            <p class="eyebrow">Operations overview</p>
            <h1>All customer orders</h1>
            <p class="subheading">Manage pending fulfilments and review delivered orders.</p>
        </div>
    </section>

    <section class="stats">
        <article class="stat">
            <span class="stat-label">Total Volume</span>
            <strong class="stat-value"><?= count($allOrdersForStats) ?></strong>
        </article>
        <article class="stat amber">
            <span class="stat-label">Needs Fulfilment</span>
            <strong class="stat-value"><?= $pendingCount ?></strong>
        </article>
        <article class="stat mint">
            <span class="stat-label">Successfully Delivered</span>
            <strong class="stat-value"><?= $deliveredCount ?></strong>
        </article>
    </section>

    <?php if ($success): ?>
        <div class="notice success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="notice error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="toolbar">
        <span class="toolbar-copy">Showing <strong><?= count($orders) ?></strong> records</span>
        <nav class="filters">
            <a class="<?= $status === '' ? 'active' : '' ?>" href="orders.php">All</a>
            <a class="<?= $status === 'Pending' ? 'active' : '' ?>" href="orders.php?status=Pending">Pending</a>
            <a class="<?= $status === 'Delivered' ? 'active' : '' ?>" href="orders.php?status=Delivered">Delivered</a>
        </nav>
    </div>

    <?php if (!$dbConnected): ?>
        <section class="empty" style="border-color:#ef4444; background:#fff5f5;">
            <h2>Orders table not found</h2>
            <p>Create an <strong>orders</strong> table in the bookstore database, then this page will pull live data from it.</p>
        </section>
    <?php elseif (!$orders): ?>
        <section class="empty">
            <div class="empty-icon">🔍</div>
            <h2>No <?= htmlspecialchars($status ? strtolower($status) . ' ' : '') ?>orders found</h2>
            <p>There are no rows inside your database matching this filter view.</p>
        </section>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer Details</th>
                        <th>Placed</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order):
                        $initial = strtoupper(substr((string) ($order['customer_name'] ?? 'U'), 0, 1));
                        $orderNumber = $order['order_number'] ?? $order['id'] ?? 'N/A';
                    ?>
                        <tr>
                            <td>
                                <span class="order-number">#<?= htmlspecialchars((string) $orderNumber) ?></span>
                            </td>

                            <td>
                                <div class="customer">
                                    <span class="avatar"><?= htmlspecialchars($initial) ?></span>
                                    <span>
                                        <strong><?= htmlspecialchars((string) ($order['customer_name'] ?? 'Guest')) ?></strong>
                                        <small style="color:#4b5563; font-weight:600; margin-top:2px;">
                                            User ID: #<?= htmlspecialchars((string) ($order['customer_id'] ?? 'N/A')) ?>
                                        </small>
                                        <small style="color:#6b7280; font-style:italic; margin-top:2px;">
                                            📍 <?= htmlspecialchars((string) ($order['delivery_address'] ?? 'No Address Listed')) ?>
                                        </small>
                                    </span>
                                </div>
                            </td>

                            <td class="date">
                                <strong><?= htmlspecialchars(date('d M Y', strtotime((string) ($order['created_at'] ?? 'now')))) ?></strong>
                                <span class="time-stamp"><?= htmlspecialchars(date('h:i A', strtotime((string) ($order['created_at'] ?? 'now')))) ?></span>
                            </td>

                            <td class="amount">
                                ৳<?= htmlspecialchars(number_format((float) ($order['total_amount'] ?? 0), 2)) ?>
                            </td>

                            <td>
                                <span class="status <?= htmlspecialchars(strtolower((string) ($order['status'] ?? 'pending'))) ?>">
                                    <?= htmlspecialchars((string) ($order['status'] ?? 'Pending')) ?>
                                </span>
                            </td>

                            <td>
                                <?php if (strtolower((string) ($order['status'] ?? '')) === 'pending'): ?>
                                    <form method="post" action="orders.php" class="action-form">
                                        <input type="hidden" name="order_id" value="<?= (int) ($order['id'] ?? 0) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <button type="submit" class="action-btn">Mark fulfilled →</button>
                                    </form>
                                <?php else: ?>
                                    <span class="done">Completed ✓</span>
                                <?php endif; ?>
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
