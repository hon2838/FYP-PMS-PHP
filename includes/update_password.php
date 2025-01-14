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

$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if (empty($token)) {
    header('Location: index.php');
    exit;
}

try {
    // Validate token and check expiration
    $stmt = $conn->prepare("SELECT * FROM tbl_users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Invalid or expired reset token");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }

        // Update password and clear reset token
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE tbl_users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        
        if ($stmt->execute([$hashedPassword, $token])) {
            showNotification(
                'success',
                'Success',
                'Password updated successfully',
                'You can now login with your new password',
                'index.php'
            );
            exit;
        } else {
            showNotification(
                'error',
                'Error',
                'Failed to update password',
                'Please try again later',
                'index.php'
            );
            exit;
        }
    }

} catch (Exception $e) {
    error_log("Password update error: " . $e->getMessage());
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password - Paperwork Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="text-center mb-4">Update Password</h4>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>