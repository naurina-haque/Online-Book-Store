<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
$error = "";

$stmt = mysqli_prepare($conn, "SELECT * FROM books WHERE book_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$book = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$book) {
    header("Location: admindashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title       = trim($_POST['title']);
    $author      = trim($_POST['author']);
    $genre       = trim($_POST['genre']);
    $price       = trim($_POST['price']);
    $description = trim($_POST['description']);
    $imageName   = $book['image'];

    if (empty($title) || empty($author) || $price === "") {
        $error = "Title, author, and price are required.";
    } elseif (!empty($_FILES['image']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $error = "Cover image must be jpg, jpeg, png, or webp.";
        } elseif ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $error = "Cover image must be under 2MB.";
        } else {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            // remove old cover if one exists
            if (!empty($book['image']) && file_exists($uploadDir . $book['image'])) {
                unlink($uploadDir . $book['image']);
            }
            $imageName = uniqid('book_') . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $imageName);
        }
    }

    if (empty($error)) {
        $stmt = mysqli_prepare($conn, "UPDATE books SET title=?, author=?, genre=?, price=?, description=?, image=? WHERE book_id=?");
        mysqli_stmt_bind_param($stmt, "sssdssi", $title, $author, $genre, $price, $description, $imageName, $id);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: admindashboard.php");
            exit();
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Book — Online Book Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="page-shell">
    <div class="book-container" style="margin: 0 auto; max-width: 500px; background: #fcfaf5; padding: 24px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
            <div>
                <h2 style="margin:0; font-family: var(--font-display);">Edit Book</h2>
            </div>
            <a href="admindashboard.php" class="back-link">← Dashboard</a>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>

        <?php if (!empty($book['image'])): ?>
            <img src="uploads/<?= htmlspecialchars($book['image']) ?>" alt="Current cover" style="width:100%; max-height:180px; object-fit:cover; border-radius:6px; margin-bottom:16px;">
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" value="<?= htmlspecialchars($book['title']) ?>" required>
            </div>
            <div class="form-group">
                <label>Author</label>
                <input type="text" name="author" value="<?= htmlspecialchars($book['author']) ?>" required>
            </div>
            <div class="form-group">
                <label>Genre</label>
                <input type="text" name="genre" value="<?= htmlspecialchars($book['genre'] ?? 'General') ?>">
            </div>
            <div class="form-group">
                <label>Price</label>
                <input type="number" step="0.01" name="price" value="<?= $book['price'] ?>" required>
            </div>
            <div class="form-group">
                <label>Cover image</label>
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4"><?= htmlspecialchars($book['description']) ?></textarea>
            </div>
            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>
</div>

</body>
</html>