<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT image FROM books WHERE book_id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$book = mysqli_stmt_get_result($stmt)->fetch_assoc();

if ($book) {
    $stmt = mysqli_prepare($conn, "DELETE FROM books WHERE book_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);


    if (!empty($book['image'])) {
        $imagePath = __DIR__ . '/uploads/' . $book['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
}

header("Location: admindashboard.php");
exit();