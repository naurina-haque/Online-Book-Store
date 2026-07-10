<?php
session_start();
include 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Check duplicate email
        $check = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email already registered.";
        } else {
            // Hash password
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $sql = "INSERT INTO users (name, email, password) 
                    VALUES ('$name', '$email', '$hashed')";

            if (mysqli_query($conn, $sql)) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register — Online Book Store</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">
    <h2>Create Account</h2>

    <?php if ($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" placeholder="Enter your name" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Min 6 characters" required>
        </div>
        <button type="submit" class="btn">Register</button>
    </form>

    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

</body>
</html>