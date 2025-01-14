<?php
// Set timezone to Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');

// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection
require_once __DIR__ . '/../dbconnect.php';

// Verify that the database connection is established
if (!isset($conn) || !$conn instanceof PDO) {
    error_log("Database connection not established in auth.php");
    die("Database connection error.");
}

class Auth {
    private static $allowed_types = ['admin', 'staff', 'hod', 'dean'];
    private static $instance = null;
    private $conn;
    private $permManager;

    // Private constructor to implement Singleton pattern
    private function __construct() {
        global $conn;
        $this->conn = $conn;

        // Initialize PermissionManager
        require_once __DIR__ . '/PermissionManager.php';
        $this->permManager = new PermissionManager($this->conn, $_SESSION['user_id']);

        // Log Permission Manager initialization
        error_log("Permission Manager initialized for user: {$_SESSION['email']}");
    }

    // Get the singleton instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Auth();
        }
        return self::$instance;
    }

    // Validate the session
    public function validateSession($requireAdmin = false) {
        // Check if essential session variables are set
        if (!isset($_SESSION['email'], $_SESSION['user_type'], $_SESSION['user_id'])) {
            error_log("Session validation failed: missing variables.");
            $this->redirect('index.php');
        }

        // Check if user_type is allowed
        if (!in_array($_SESSION['user_type'], self::$allowed_types, true)) {
            error_log("Invalid user type: " . $_SESSION['user_type']);
            $this->redirect('index.php');
        }

        // If admin access is required, verify user type
        if ($requireAdmin && $_SESSION['user_type'] !== 'admin') {
            error_log("Admin access required but user type is: " . $_SESSION['user_type']);
            $this->redirect('dashboard.php');
        }

        if (!in_array($_SESSION['user_type'], self::$allowed_types)) {
            error_log("Invalid user type: {$_SESSION['user_type']}");
            $this->redirect('index.php');
        }

        $this->checkSessionTimeout();
        $this->preventSessionFixation();
        $this->initializePermissionManager();
    }

    private function checkSessionTimeout() {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            $this->redirect('index.php');
        }
        $_SESSION['last_activity'] = time();
    }

    private function preventSessionFixation() {
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        }
    }

    private function initializePermissionManager() {
        if (!isset($this->permManager)) {
            require_once 'PermissionManager.php';
            $this->permManager = new PermissionManager($this->conn, $_SESSION['user_id']);
            error_log("Permission Manager initialized for user: {$_SESSION['email']}");
        }
    }

    // Retrieve the database connection
    public function getConnection() {
        return $this->conn;
    }

    // Retrieve the PermissionManager instance
    public function getPermManager() {
        return $this->permManager;
    }

    // Require a specific permission
    public function requirePermission($permission) {
        $this->permManager->requirePermission($permission);
    }

    // Get user email
    public function getUserEmail() {
        return $_SESSION['email'] ?? 'not set';
    }

    // Get user type
    public function getUserType() {
        return $_SESSION['user_type'] ?? 'not set';
    }

    // Get user ID
    public function getUserId() {
        return $_SESSION['user_id'] ?? 'not set';
    }

    // Redirect to a specified page
    private function redirect($page) {
        header("Location: $page");
        exit;
    }
}

// Initialize authentication
$auth = Auth::getInstance();
$auth->validateSession();

// Log the end of the auth check
error_log("=== Auth Check End ===");