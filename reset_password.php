<?php
require_once 'telegram/telegram_handlers.php';
include 'dbconnect.php';

// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Set security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");

// Rate limiting
if (!isset($_SESSION['reset_attempts'])) {
    $_SESSION['reset_attempts'] = 1;
    $_SESSION['reset_time'] = time();
} else {
    if (time() - $_SESSION['reset_time'] < 300) { // 5 minute window
        if ($_SESSION['reset_attempts'] > 3) {
            error_log("Password reset rate limit exceeded from IP: " . $_SERVER['REMOTE_ADDR']);
            die("Too many reset attempts. Please try again later.");
        }
        $_SESSION['reset_attempts']++;
    } else {
        $_SESSION['reset_attempts'] = 1;
        $_SESSION['reset_time'] = time();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM tbl_users WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Email not found");
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store reset token
        $stmt = $conn->prepare("UPDATE tbl_users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        if (!$stmt->execute([$token, $expires, $email])) {
            throw new Exception("Failed to generate reset token");
        }

        // Send reset email
        include 'includes/email_functions.php';
        $resetLink = "http://{$_SERVER['HTTP_HOST']}update_password.php?token=" . $token;
        sendPasswordResetEmail($email, $resetLink);

        echo "<script>alert('Password reset instructions have been sent to your email.');</script>";
        echo "<script>window.location.href='index.php';</script>";

    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Paperwork Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4">Reset Password</h4>
                        <form method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    Send Reset Link
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>