<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

$bookId = intval($_GET['id'] ?? $_POST['book_id'] ?? 0);
$error = "";
$success = "";

$stmt = mysqli_prepare($conn, "SELECT * FROM books WHERE book_id = ?");
mysqli_stmt_bind_param($stmt, "i", $bookId);
mysqli_stmt_execute($stmt);
$book = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$book) {
    header("Location: customerdashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $address  = trim($_POST['delivery_address'] ?? '');

    if (empty($address)) {
        $error = "Delivery address is required.";
    } else {
        $totalAmount = $book['price'] * $quantity;
        $orderNumber = 'ORD-' . date('Y') . '-' . strtoupper(uniqid());
        $itemsText   = $book['title'] . " (Qty: $quantity @ \$" . number_format($book['price'], 2) . ")";

        $stmt = mysqli_prepare($conn, "INSERT INTO orders (order_number, customer_name, customer_id, delivery_address, items, total_amount, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
        mysqli_stmt_bind_param(
            $stmt,
            "ssisid",
            $orderNumber,
            $_SESSION['name'],
            $_SESSION['user_id'],
            $address,
            $itemsText,
            $totalAmount
        );

        if (mysqli_stmt_execute($stmt)) {
            $success = "Order placed successfully! Order #$orderNumber";
        } else {
            $error = "Something went wrong placing your order. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout — Online Book Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="checkout-shell">
    <?php if ($success): ?>
        <div class="checkout-card checkout-success-card">
            <div class="alert success"><?= $success ?></div>
            <a href="customerdashboard.php" class="btn" style="display:block; text-align:center; text-decoration:none; margin-top:12px;">Continue browsing</a>
        </div>
    <?php else: ?>

    <div class="checkout-card">
        <div class="checkout-panel">
            <span class="auth-eyebrow" style="color: var(--brass-soft);">Checkout</span>
            <?php if (!empty($book['image'])): ?>
                <img src="uploads/<?= htmlspecialchars($book['image']) ?>" alt="<?= htmlspecialchars($book['title']) ?>" class="checkout-cover">
            <?php else: ?>
                <div class="checkout-cover checkout-cover-placeholder">No cover</div>
            <?php endif; ?>
            <div class="checkout-title"><?= htmlspecialchars($book['title']) ?></div>
            <div class="checkout-author"><?= htmlspecialchars($book['author']) ?></div>
            <div class="checkout-genre-badge"><?= htmlspecialchars($book['genre'] ?? 'General') ?></div>
            <?php if (!empty($book['description'])): ?>
                <div class="checkout-description"><?= htmlspecialchars($book['description']) ?></div>
            <?php endif; ?>
            <div class="checkout-unit-price">
                $<?= number_format($book['price'], 2) ?> <span>each</span>
            </div>
        </div>

        <div class="checkout-form-side">
            <h2 class="section-title" style="margin-bottom:20px;">Order details</h2>

            <?php if ($error): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="orderForm">
                <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">

                <div class="form-group">
                    <label>Quantity</label>
                    <div class="qty-stepper">
                        <button type="button" class="qty-btn" id="qtyMinus">−</button>
                        <input type="number" name="quantity" id="qtyInput" value="1" min="1" required>
                        <button type="button" class="qty-btn" id="qtyPlus">+</button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Delivery address</label>
                    <textarea style="width: 100%; padding: 8px;" name="delivery_address" rows="3" required placeholder="House, Road, Area, City"></textarea>
                </div>

                <div class="checkout-summary">
                    <div class="checkout-summary-row">
                        <span>Subtotal</span>
                        <span id="subtotalDisplay">$<?= number_format($book['price'], 2) ?></span>
                    </div>
                    <div class="checkout-summary-row checkout-summary-total">
                        <span>Total</span>
                        <span id="totalDisplay">$<?= number_format($book['price'], 2) ?></span>
                    </div>
                </div>

                <button type="submit" class="btn">Place Order</button>
            </form>
            <div class="back-link-container">
                <a href="customerdashboard.php" class="back-link">← Back to browsing</a>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
    const unitPrice = <?= $book['price'] ?>;
    const qtyInput = document.getElementById('qtyInput');
    const subtotalDisplay = document.getElementById('subtotalDisplay');
    const totalDisplay = document.getElementById('totalDisplay');

    function updateTotal() {
        let qty = parseInt(qtyInput.value) || 1;
        if (qty < 1) qty = 1;
        const total = (unitPrice * qty).toFixed(2);
        subtotalDisplay.textContent = '$' + total;
        totalDisplay.textContent = '$' + total;
    }

    document.getElementById('qtyMinus').addEventListener('click', function () {
        qtyInput.value = Math.max(1, parseInt(qtyInput.value || 1) - 1);
        updateTotal();
    });

    document.getElementById('qtyPlus').addEventListener('click', function () {
        qtyInput.value = parseInt(qtyInput.value || 1) + 1;
        updateTotal();
    });

    qtyInput.addEventListener('input', updateTotal);
</script>

</body>
</html>