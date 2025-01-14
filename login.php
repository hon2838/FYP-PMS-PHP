<?php
require_once 'telegram/telegram_handlers.php';
require_once 'dbconnect.php'; // Include database connection first

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

// Only initialize Auth after successful login
if (isset($_SESSION['email']) && isset($_SESSION['user_type']) && isset($_SESSION['user_id'])) {
    require_once 'includes/auth.php';
    $auth = Auth::getInstance();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Get user data
        $stmt = $conn->prepare("SELECT * FROM tbl_users WHERE email = ? AND active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify credentials
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['created'] = time();
            
            // Now initialize Auth system
            require_once 'includes/auth.php';
            $auth = Auth::getInstance();
            
            header('Location: dashboard.php');
            exit;
        } else {
            throw new Exception("Invalid credentials");
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $error = "Login failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SOCPMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <!-- Login Section -->
    <div class="auth-wrapper">
        <div class="auth-overlay"></div>
        <div class="auth-container">
            <div class="mx-auto" style="max-width: 420px;">
                <div class="auth-card">
                    <div class="card-body p-4">
                        <!-- Logo Section -->
                        <div class="text-center mb-4">
                            <div class="app-logo mb-3">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h3 class="fw-bold">Welcome Back</h3>
                            <p class="text-muted">Login to your account</p>
                        </div>

                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger alert-modern" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form action="login.php" method="post" class="needs-validation" novalidate>
                            <!-- Email Field -->
                            <div class="form-floating mb-4">
                                <input type="email" 
                                       class="form-control modern-input" 
                                       id="email" 
                                       name="email" 
                                       placeholder="Email" 
                                       required
                                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                                <label for="email">Email Address</label>
                                <div class="invalid-feedback">
                                    Please enter a valid email address.
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div class="form-floating mb-4">
                                <input type="password" 
                                       class="form-control modern-input" 
                                       id="password" 
                                       name="password" 
                                       placeholder="Password" 
                                       required
                                       minlength="6">
                                <label for="password">Password</label>
                                <div class="invalid-feedback">
                                    Password is required and must be at least 6 characters.
                                </div>
                            </div>

                            <!-- Login Button -->
                            <button type="submit" class="btn btn-primary w-100 btn-modern mb-3">
                                <span class="btn-text">Login</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>

                            <!-- Portal Login Button -->
                            <button type="button" class="btn btn-outline-primary w-100 btn-modern" data-bs-toggle="modal" data-bs-target="#portalModal">
                                <i class="fas fa-university me-2"></i>
                                <span class="btn-text">Login with Portal</span>
                            </button>

                            <div class="text-center mt-4">
                                <p class="mb-0 text-muted">
                                    Don't have an account? <a href="register.php" class="link-primary text-decoration-none">Register</a>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>

    <!-- Portal Login Modal -->
    <div class="modal fade" id="portalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-tools text-primary me-2"></i>
                        Feature in Development
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="development-animation mb-4">
                        <i class="fas fa-code text-primary" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="mb-3">Portal Integration Coming Soon!</h5>
                    <p class="text-muted mb-0">We're working hard to bring you seamless portal integration. Please use email login for now.</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Got it!</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Validation and Responsive Design Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const forms = document.querySelectorAll('.needs-validation');
        if (forms.length > 0) {
            forms.forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');

                    // Additional validation
                    const emailInput = form.querySelector('#email');
                    const passwordInput = form.querySelector('#password');
                    
                    if (emailInput && !emailInput.value) {
                        event.preventDefault();
                        emailInput.classList.add('is-invalid');
                    }
                    
                    if (passwordInput && !passwordInput.value) {
                        event.preventDefault();
                        passwordInput.classList.add('is-invalid');
                    }
                });
            });
        }

        // Theme initialization and responsive handling
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);

        // Responsive design adjustments
        function handleResponsiveLayout() {
            const width = window.innerWidth;
            const loginContainer = document.querySelector('.login-container');
            const cardBody = document.querySelector('.card-body');
            
            if (width < 576) { // Mobile
                if (loginContainer) {
                    loginContainer.classList.remove('p-5');
                    loginContainer.classList.add('p-3');
                }
                if (cardBody) {
                    cardBody.classList.remove('p-5');
                    cardBody.classList.add('p-3');
                }
            } else if (width < 768) { // Tablet
                if (loginContainer) {
                    loginContainer.classList.remove('p-5', 'p-3');
                    loginContainer.classList.add('p-4');
                }
                if (cardBody) {
                    cardBody.classList.remove('p-5', 'p-3');
                    cardBody.classList.add('p-4');
                }
            } else { // Desktop
                if (loginContainer) {
                    loginContainer.classList.remove('p-3', 'p-4');
                    loginContainer.classList.add('p-5');
                }
                if (cardBody) {
                    cardBody.classList.remove('p-3', 'p-4');
                    cardBody.classList.add('p-5');
                }
            }
        }

        // Initial call
        handleResponsiveLayout();

        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(handleResponsiveLayout, 250);
        });

        // Handle orientation change for mobile devices
        window.addEventListener('orientationchange', handleResponsiveLayout);

        // Handle dynamic content loading
        const observer = new MutationObserver(handleResponsiveLayout);
        observer.observe(document.body, { 
            childList: true, 
            subtree: true 
        });
    });
    </script>

    <!-- Add viewport-specific styles -->
    <style>
    @media (max-width: 576px) {
        .navbar-brand span {
            font-size: 1rem;
        }
        .form-control-lg {
            font-size: 1rem;
            padding: 0.5rem 0.75rem;
        }
        .btn-lg {
            padding: 0.5rem 1rem;
            font-size: 1rem;
        }
        .h4 {
            font-size: 1.25rem;
        }
    }

    @media (max-width: 768px) {
        .navbar {
            padding: 0.5rem 1rem;
        }
        .modal-dialog {
            margin: 0.5rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        * {
            transition: none !important;
        }
    }
    </style>
       <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>