<?php
require_once 'telegram/telegram_handlers.php';

// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Set security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

include 'dbconnect.php'; // Adjust the path if necessary

// Rate limiting with notifications
if (!isset($_SESSION['register_attempts'])) {
    $_SESSION['register_attempts'] = 1;
    $_SESSION['register_time'] = time();
} else {
    if (time() - $_SESSION['register_time'] < 300) { // 5 minute window
        if ($_SESSION['register_attempts'] > 3) {
            // Log and notify about rate limit breach
            error_log("Registration rate limit exceeded from IP: " . $_SERVER['REMOTE_ADDR']);
            notifySystemError(
                'Rate Limit Exceeded',
                "Multiple registration attempts from IP: {$_SERVER['REMOTE_ADDR']}",
                __FILE__,
                __LINE__
            );
            die("Too many registration attempts. Please try again later.");
        }
        $_SESSION['register_attempts']++;
    } else {
        $_SESSION['register_attempts'] = 1;
        $_SESSION['register_time'] = time();
    }
}

// Initialize variables for form validation
$name = $password = $confirmPassword = $email = '';
$name_err = $password_err = $confirmPassword_err = '';

// Input validation function
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Password strength validation
function validatePassword($password) {
    if (strlen($password) < 8) {
        return "Password must be at least 8 characters long";
    }
    if (!preg_match("/[A-Z]/", $password)) {
        return "Password must contain at least one uppercase letter";
    }
    if (!preg_match("/[a-z]/", $password)) {
        return "Password must contain at least one lowercase letter";
    }
    if (!preg_match("/[0-9]/", $password)) {
        return "Password must contain at least one number";
    }
    if (!preg_match("/[!@#$%^&*()\-_=+{};:,<.>]/", $password)) {
        return "Password must contain at least one special character";
    }
    return "";
}

// Process registration form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate inputs
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Email format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM tbl_users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            // Notify about duplicate registration attempt
            notifySystemError(
                'Registration Attempt',
                "Duplicate registration attempt for email: $email\nIP: {$_SERVER['REMOTE_ADDR']}",
                __FILE__,
                __LINE__
            );
            throw new Exception("Email already exists");
        }
        
        // Insert new user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO tbl_users (name, email, password, user_type, department) 
                               VALUES (?, ?, ?, 'staff', ?)");
        $params = [
            $name, 
            $email, 
            $hashedPassword,
            $_POST['department']
        ];

        if ($stmt->execute($params)) {
            // Get the auto-generated id
            $userId = $conn->lastInsertId();
            
            // Set default settings
            $defaultSettings = json_encode([
                'theme' => 'light',
                'email_notifications' => true,
                'browser_notifications' => false,
                'compact_view' => false
            ]);

            $settingsStmt = $conn->prepare("UPDATE tbl_users SET settings = ? WHERE id = ?");
            $settingsStmt->execute([$defaultSettings, $userId]);

            // Insert default role for staff
            $roleStmt = $conn->prepare("INSERT INTO tbl_user_roles (user_id, role_id) VALUES (?, 5)"); // 5 is staff role
            $roleStmt->execute([$userId]);

            // Notify admin and redirect
            notifySystemError(
                'New User Registration',
                "New user registered:\nName: $name\nEmail: $email\nID: $userId\nIP: {$_SERVER['REMOTE_ADDR']}\nTime: " . date('Y-m-d H:i:s'),
                __FILE__,
                __LINE__
            );
            
            header("Location: index.php");
            exit();
        }
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        // Notify admin about registration error
        notifySystemError(
            'Registration Error',
            $e->getMessage(),
            __FILE__,
            __LINE__
        );
        die("An error occurred during registration. Please try again later.");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SOC PMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
    /* Responsive form styling */
    .registration-container {
        max-width: 100%;
        margin: 0 auto;
        transition: padding 0.3s ease;
    }

    /* Form field spacing */
    .form-group {
        margin-bottom: 1.5rem;
    }

    /* Input group enhancements */
    .input-group {
        position: relative;
        transition: all 0.3s ease;
    }

    .input-group-text {
        background-color: var(--bg-secondary);
        border: none;
        color: var(--text-secondary);
    }

    .form-control,
    .form-select {
        border: none;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    /* Responsive card */
    .card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    /* Responsive padding adjustments */
    @media (min-width: 992px) {
        .registration-container {
            max-width: 900px;
            padding: 2rem;
        }
        .card-body {
            padding: 3rem;
        }
    }

    @media (min-width: 768px) and (max-width: 991px) {
        .registration-container {
            max-width: 720px;
            padding: 1.5rem;
        }
        .card-body {
            padding: 2rem;
        }
    }

    @media (min-width: 576px) and (max-width: 767px) {
        .registration-container {
            padding: 1rem;
        }
        .card-body {
            padding: 1.5rem;
        }
        .form-control-lg, 
        .form-select-lg {
            font-size: 1rem;
            padding: 0.5rem 0.75rem;
        }
    }

    @media (max-width: 575px) {
        .registration-container {
            padding: 0.5rem;
        }
        .card-body {
            padding: 1.25rem;
        }
        .form-control-lg, 
        .form-select-lg {
            font-size: 0.95rem;
            padding: 0.5rem 0.75rem;
        }
        .btn-lg {
            padding: 0.5rem 1rem;
            font-size: 1rem;
        }
    }
    </style>
</head>
<body class="bg-light">
    <!-- Registration Section -->
    <div class="auth-wrapper">
        <div class="auth-overlay"></div>
        <div class="auth-container">
            <div class="mx-auto" style="max-width: 480px;">
                <div class="auth-card">
                    <div class="card-body p-4">
                        <!-- Logo Section -->
                        <div class="text-center mb-4">
                            <div class="app-logo mb-3">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3 class="fw-bold">Create Account</h3>
                            <p class="text-muted">Join Paperwork Management System</p>
                        </div>

                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger alert-modern" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form action="register.php" method="post" class="needs-validation" novalidate>
                            <!-- Form Fields -->
                            <div class="row g-4">
                                <!-- Full Name Field -->
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control modern-input" id="name" name="name" placeholder="Full Name" required>
                                        <label for="name">Full Name</label>
                                    </div>
                                </div>

                                <!-- Email Field -->
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="email" class="form-control modern-input" id="email" name="email" placeholder="Email" required>
                                        <label for="email">Email Address</label>
                                    </div>
                                </div>

                                <!-- Staff ID Field -->
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control modern-input" id="staff_id" name="staff_id" placeholder="Staff ID" required>
                                        <label for="staff_id">Staff ID</label>
                                    </div>
                                </div>

                                <!-- Department Field -->
                                <div class="col-12">
                                    <div class="form-floating">
                                        <select class="form-select modern-input" id="department" name="department" required>
                                            <option value="" selected disabled>Select Department</option>
                                            <option value="Software Engineering">Software Engineering</option>
                                            <option value="Network Security">Network Security</option>
                                            <option value="Data Science">Data Science</option>
                                        </select>
                                        <label for="department">Department</label>
                                    </div>
                                </div>

                                <!-- Password Fields -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="password" class="form-control modern-input" id="password" name="password" placeholder="Password" required>
                                        <label for="password">Password</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="password" class="form-control modern-input" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                                        <label for="confirm_password">Confirm Password</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Register Button -->
                            <button type="submit" class="btn btn-primary w-100 btn-modern mt-4">
                                <span class="btn-text">Create Account</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>

                            <div class="text-center mt-4">
                                <p class="mb-0 text-muted">
                                    Already have an account? <a href="index.php" class="link-primary text-decoration-none">Login</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Responsive adjustments
        function handleResponsiveLayout() {
            const width = window.innerWidth;
            const formContainer = document.querySelector('.login-container');
            const cardBody = document.querySelector('.card-body');
            
            if (width < 576) {
                formContainer?.classList.remove('p-5');
                formContainer?.classList.add('p-3');
                cardBody?.classList.remove('p-5');
                cardBody?.classList.add('p-3');
            } else if (width < 768) {
                formContainer?.classList.remove('p-5', 'p-3');
                formContainer?.classList.add('p-4');
                cardBody?.classList.remove('p-5', 'p-3');
                cardBody?.classList.add('p-4');
            } else {
                formContainer?.classList.remove('p-3', 'p-4');
                formContainer?.classList.add('p-5');
                cardBody?.classList.remove('p-3', 'p-4');
                cardBody?.classList.add('p-5');
            }
        }

        // Initial call and event listeners
        handleResponsiveLayout();
        window.addEventListener('resize', handleResponsiveLayout);
        window.addEventListener('orientationchange', handleResponsiveLayout);
    });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });

        // Responsive adjustments
        function handleResponsiveLayout() {
            const width = window.innerWidth;
            const container = document.querySelector('.registration-container');
            const card = document.querySelector('.card');
            
            if (width < 576) { // Mobile
                container?.classList.add('px-2');
                card?.classList.add('mx-2');
            } else {
                container?.classList.remove('px-2');
                card?.classList.remove('mx-2');
            }
        }

        // Initial call and event listeners
        handleResponsiveLayout();
        window.addEventListener('resize', handleResponsiveLayout);
        window.addEventListener('orientationchange', handleResponsiveLayout);
    });
    </script>
</body>
</html>
