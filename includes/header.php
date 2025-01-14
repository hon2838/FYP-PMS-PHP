<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'read_and_close'  => true, // Allow multiple concurrent sessions
        'cookie_httponly' => 1,
        'cookie_samesite' => 'Strict'
    ]);
}

// Enhanced header logging
error_log("=== Header Include Start ===");
error_log("Page: " . basename($_SERVER['PHP_SELF']));
error_log("Session State: " . json_encode([
    'email' => $_SESSION['email'] ?? 'not set',
    'user_type' => $_SESSION['user_type'] ?? 'not set',
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'session_active' => session_status() === PHP_SESSION_ACTIVE,
    'allowed_types' => ['admin', 'staff', 'hod', 'dean'],
    'referrer' => $_SERVER['HTTP_REFERER'] ?? 'none',
    'time' => date('Y-m-d H:i:s')
]));

// Basic session validation
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    error_log("Header validation failed: " . json_encode([
        'email_set' => isset($_SESSION['email']),
        'user_type_set' => isset($_SESSION['user_type']),
        'user_id_set' => isset($_SESSION['user_id'])
    ]));
    header('Location: index.php');
    exit;
}

// Allow any valid user type without forcing admin
$allowed_types = ['admin', 'staff', 'hod', 'dean'];
if (!in_array($_SESSION['user_type'], $allowed_types)) {
    error_log("Invalid user type detected: " . json_encode([
        'user_type' => $_SESSION['user_type'],
        'email' => $_SESSION['email']
    ]));
    header('Location: index.php');
    exit;
}

// Get auth instance and permission manager
$auth = Auth::getInstance();
$permManager = $auth->getPermManager();

error_log("=== Header Include End ===");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paperwork Management System</title>
    
    <!-- Preload dark mode styles to prevent flash -->
    <style>
        /* Preload dark mode variables */
        :root[data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #ced4da;
            --border-color: #404040;
            --card-bg: #2d2d2d;
            --navbar-bg: #2d2d2d;
            --table-bg: #2d2d2d;
        }

        /* Apply dark background immediately if theme is dark */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1a1a1a !important;
            }
        }
        
        /* Prevent flash during loading */
        body {
            visibility: hidden;
            transition: background-color 0.2s ease;
        }
        
        body.theme-loaded {
            visibility: visible;
        }

        .brand-title {
        display: flex;
        flex-direction: column;
        line-height: 1.2;
        }
        .brand-title .main-title {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .brand-title .sub-title {
            font-size: 0.9rem;
        }
        .brand-icon {
            font-size: 2.5rem;
            margin-right: 0.75rem;
        }
    </style>
    
    <!-- CSS includes -->
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Animate.css for Animations -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <!-- Your Custom Styles -->
    <link href="style.css" rel="stylesheet">
    <script src="js/notifications.js"></script>

    <script>
        // Initialize theme before page load
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            if (savedTheme === 'dark' || (savedTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
            document.addEventListener('DOMContentLoaded', function() {
                document.body.classList.add('theme-loaded');
            });
        })();
    </script>
</head>


<body class="bg-light">
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
        <a class="navbar-brand d-flex align-items-center py-2" href="<?php echo isset($_SESSION['user_type']) ? 'dashboard.php' : 'index.php'; ?>">
            <i class="fas fa-file-alt text-primary brand-icon"></i>
            <div class="brand-title">
                <span class="main-title">SOC Paperwork</span>
                <span class="sub-title">Management System</span>
            </div>
        </a>
            
            <?php if(isset($_SESSION['email'])): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Navigation Links -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?> px-3" 
                        href="dashboard.php">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                    </li>
                    
                    <!-- Create Paperwork Link (not for dean) -->
                    <?php if ($_SESSION['user_type'] !== 'dean'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'create_paperwork.php' ? 'active' : ''; ?> px-3" 
                           href="create_paperwork.php">
                            <i class="fas fa-plus me-1"></i> New Paperwork
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- User Profile Link (for non-admin users) -->
                    <?php if ($_SESSION['user_type'] !== 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'edit_profile.php' ? 'active' : ''; ?> px-3" 
                           href="edit_profile.php">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Admin Management Link (for admin users) -->
                    <?php if ($_SESSION['user_type'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin_manage_account.php' ? 'active' : ''; ?> px-3" 
                           href="admin_manage_account.php">
                            <i class="fas fa-users-cog me-2"></i>Manage Users
                        </a>
                    </li>
                    <?php endif; ?>
                    <!-- Add this after the Manage Accounts link -->
                    <?php if (in_array($_SESSION['user_type'], ['dean', 'admin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'audit_log.php' ? 'active' : ''; ?> px-3" 
                           href="audit_log.php">
                            <i class="fas fa-history me-2"></i>Audit Log
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#helpModal">
                            <i class="fas fa-question-circle me-2"></i>Help
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal">
                            <i class="fas fa-info-circle me-2"></i>About
                        </a>
                    </li>
                    <li class="nav-item dropdown ms-3">
                        <button class="nav-link dropdown-toggle btn btn-link" 
                                type="button" 
                                id="navbarDropdown" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false">
                            <i class="fas fa-user-circle me-2"></i>
                            <?php echo htmlspecialchars($_SESSION['name']); ?>
                            <span class="badge bg-primary ms-2"><?php echo ucfirst(htmlspecialchars($_SESSION['user_type'])); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" aria-labelledby="navbarDropdown">
                            <li>
                                <a class="nav-link px-3" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal">
                                    <i class="fas fa-cog me-1"></i> Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    <main>
    <!-- Include modals -->
    <?php include 'includes/modals/about.php'; ?>
    <?php include 'includes/modals/help.php'; ?>
    <?php include 'includes/modals/notification.php'; ?>
    <?php include 'includes/modals/settings.php'; ?>
    <!-- Add before closing body tag -->
	<!-- Add before closing body tag -->
    <?php include 'includes/chatbot/chat_widget.php'; ?>
    <script src="js/chatbot.js"></script>

    <!-- Required JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/notifications.js"></script>

    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Theme initialization
        const initTheme = () => {
            const savedTheme = localStorage.getItem('theme') || 'light';
            applyTheme(savedTheme);
            document.body.classList.add('theme-loaded');
        };

        // Theme switching function
        const applyTheme = (theme) => {
            if (theme === 'system') {
                const isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-theme', isDarkMode ? 'dark' : 'light');
            } else {
                document.documentElement.setAttribute('data-theme', theme);
            }
            localStorage.setItem('theme', theme);
        };

        // Initialize Bootstrap components
        const initBootstrapComponents = () => {
            // Initialize dropdowns
            document.querySelectorAll('.dropdown-toggle').forEach(el => {
                new bootstrap.Dropdown(el);
            });

            // Initialize tooltips
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                new bootstrap.Tooltip(el);
            });

            // Initialize modals
            document.querySelectorAll('.modal').forEach(el => {
                new bootstrap.Modal(el);
            });
        };

        // Settings form handler
        const initSettingsForm = () => {
            const form = document.getElementById('settingsForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('settingsModal'));

                    fetch('update_settings.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            applyTheme(data.theme);
                            document.body.classList.toggle('compact-view', data.settings.compact_view);
                            
                            NotificationModal.show({
                                type: 'success',
                                title: 'Success',
                                message: 'Settings updated successfully'
                            });

                            if (modal) {
                                modal.hide();
                            }
                        } else {
                            throw new Error(data.error || 'Error updating settings');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        NotificationModal.show({
                            type: 'error',
                            title: 'Error',
                            message: 'Failed to update settings',
                            details: error.message
                        });
                    });
                });
            }
        };

        // Initialize everything
        initTheme();
        initBootstrapComponents();
        initSettingsForm();

        // Handle system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (localStorage.getItem('theme') === 'system') {
                applyTheme('system');
            }
        });
    });
    </script>
