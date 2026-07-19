<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

$search = trim($_GET['search'] ?? '');
$genreFilter = trim($_GET['genre'] ?? '');

// build query dynamically based on filters
$conditions = [];
$params = [];
$types = "";

if ($search !== '') {
    $conditions[] = "(title LIKE ? OR author LIKE ?)";
    $likeTerm = "%$search%";
    $params[] = $likeTerm;
    $params[] = $likeTerm;
    $types .= "ss";
}

if ($genreFilter !== '' && $genreFilter !== 'All') {
    $conditions[] = "genre = ?";
    $params[] = $genreFilter;
    $types .= "s";
}

$whereClause = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";
$sql = "SELECT * FROM books $whereClause ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== "") {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// pull distinct genres for the filter dropdown
$genreOptions = mysqli_query($conn, "SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre != '' ORDER BY genre ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Books — Online Book Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="dash-layout">

    <div class="dash-sidebar">
        <div class="dash-brand">Book Store</div>
        <nav class="dash-nav">
            <a href="customerdashboard.php" class="active">Browse Books</a>
            <a href="order_history.php">My Orders</a>
        </nav>
        <a href="logout.php" class="dash-logout">Log out</a>
    </div>

    <div class="dash-main">
        <div class="page-header" style="border:none; margin-bottom:20px; padding-bottom:0;">
            <div>
                <span class="eyebrow">Catalogue</span>
                <h2>Browse Books</h2>
            </div>
        </div>

        <form method="GET" action="" class="search-bar">
            <input
                type="text"
                name="search"
                placeholder="Search by title or author..."
                value="<?= htmlspecialchars($search) ?>"
                class="search-input"
            >
            <select name="genre" class="search-select">
                <option value="All">All genres</option>
                <?php while ($g = mysqli_fetch_assoc($genreOptions)): ?>
                    <option value="<?= htmlspecialchars($g['genre']) ?>" <?= $genreFilter === $g['genre'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['genre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn-add">Search</button>
            <?php if ($search !== '' || $genreFilter !== ''): ?>
                <a href="customerdashboard.php" class="back-link" style="margin:0 0 0 8px;">Clear</a>
            <?php endif; ?>
        </form>

        <div class="book-card-grid" style="margin-top:24px;">
            <?php
            $spineColors = ['#1F3864', '#B08D57', '#7C8B6F', '#A6503B', '#5B4E8A'];
            $hasBooks = false;
            while ($book = mysqli_fetch_assoc($result)):
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
                        <a href="place_order.php?id=<?= $book['book_id'] ?>" class="buy-btn">Buy</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

            <?php if (!$hasBooks): ?>
            <div class="empty-shelf" style="grid-column: 1 / -1;">
                <p>No books found</p>
                <p>Try a different search term or genre.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>