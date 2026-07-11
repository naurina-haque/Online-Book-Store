<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$latestBooks = mysqli_query($conn, "SELECT * FROM books ORDER BY created_at DESC LIMIT 5");

$totalBooks      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM books"))['c'];
$totalAuthors    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT author) AS c FROM books"))['c'];
$totalCategories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT genre) AS c FROM books"))['c'];
$totalCustomers  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'customer'"))['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard — Online Book Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dash-layout">

    <div class="dash-sidebar">
        <div class="dash-brand">Book Store</div>
        <nav class="dash-nav">
            <a href="admindashboard.php" class="active">Dashboard</a>
            <a href="books.php">Books</a>
            <a href="#">Orders</a>
            <a href="#">Customers</a>
        </nav>
        <a href="logout.php" class="dash-logout">Log out</a>
    </div>

    <div class="dash-main">
        <div class="page-header">
            <div>
                <span class="eyebrow">Admin</span>
                <h2>Dashboard</h2>
            </div>
            <a href="add_book.php" class="btn-add">+ Add New Book</a>
        </div>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-label">Total books</div>
                <div class="stat-value"><?= $totalBooks ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Authors</div>
                <div class="stat-value"><?= $totalAuthors ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Categories</div>
                <div class="stat-value"><?= $totalCategories ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Customers</div>
                <div class="stat-value"><?= $totalCustomers ?></div>
            </div>
        </div>

        <div class="section-row">
            <div class="section-title">Latest books</div>
            <a href="books.php" class="view-all-link">View all books</a>
        </div>

        <div class="book-card-grid">
            <?php
            $spineColors = ['#1F3864', '#B08D57', '#7C8B6F', '#A6503B', '#5B4E8A'];
            $hasBooks = false;
            while ($book = mysqli_fetch_assoc($latestBooks)):
                $hasBooks = true;
                $spine = $spineColors[$book['book_id'] % count($spineColors)];
            ?>
            <div class="book-card">
                <?php if (!empty($book['image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($book['image']) ?>" alt="<?= htmlspecialchars($book['title']) ?>" class="book-cover">
                <?php else: ?>
                    <div class="book-cover book-cover-placeholder" style="background: <?= $spine ?>;">No cover</div>
                <?php endif; ?>
                <div class="book-card-body">
                    <div class="book-card-title"><?= htmlspecialchars($book['title']) ?></div>
                    <div class="book-card-author"><?= htmlspecialchars($book['author']) ?></div>
                    <div class="book-card-genre"><?= htmlspecialchars($book['genre']) ?></div>
                    <div class="book-card-footer">
                        <span class="book-card-price">$<?= number_format($book['price'], 2) ?></span>
                        <span class="book-card-actions">
                            <a href="edit_book.php?id=<?= $book['book_id'] ?>">Edit</a>
                            <a href="delete_book.php?id=<?= $book['book_id'] ?>" class="delete-link" onclick="return confirm('Delete this book?');">Delete</a>
                        </span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

            <?php if (!$hasBooks): ?>
            <div class="empty-shelf" style="grid-column: 1 / -1;">
                <p>The shelf is empty</p>
                <p>Add your first book to get the catalogue started.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>