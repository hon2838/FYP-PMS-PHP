<?php
ob_start();
// dashboard.php

// Include Database Connection First
require_once 'dbconnect.php';

// Existing Includes
require_once 'telegram/telegram_handlers.php';
require_once 'includes/auth.php';
require_once 'includes/PermissionManager.php';
require_once 'includes/utilities.php'; // Ensure utilities.php contains the showNotification function
require_once 'includes/audit_logger.php'; // Ensure audit logging is available

// Initialize Auth and Permission Manager
$auth = Auth::getInstance();
$permManager = $auth->getPermManager();
error_log("Permission Manager initialized for user: {$_SESSION['email']} with type: {$_SESSION['user_type']}");

// Include the Delete Handler After Initializing Dependencies
require_once 'includes/paperwork/delete_handler.php'; // Include the delete handler

// Add detailed session logging
error_log("Dashboard Access - Session Data: " . json_encode([
    'email' => $_SESSION['email'] ?? 'not set',
    'user_type' => $_SESSION['user_type'] ?? 'not set',
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'page' => basename($_SERVER['PHP_SELF']),
    'referrer' => $_SERVER['HTTP_REFERER'] ?? 'none',
    'time' => date('Y-m-d H:i:s')
]));

// Validate session with proper user type check
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type']) || !isset($_SESSION['user_id'])) {
    error_log("Session validation failed in dashboard.php: " . json_encode([
        'email_set' => isset($_SESSION['email']),
        'user_type_set' => isset($_SESSION['user_type']),
        'user_id_set' => isset($_SESSION['user_id']),
        'session_data' => $_SESSION
    ]));
    header('Location: index.php');
    exit;
}

// Handle Deletion Request **After Initializing Dependencies**
if (isset($_GET['submit']) && $_GET['submit'] === 'delete' && isset($_GET['ppw_id'])) {
    try {
        $ppw_id = filter_var($_GET['ppw_id'], FILTER_VALIDATE_INT);
        if (!$ppw_id) {
            throw new Exception("Invalid paperwork ID.");
        }

        error_log("Processing deletion request - PPW ID: $ppw_id, User: {$_SESSION['email']}");

        if (handlePaperworkDeletion($conn, $ppw_id, $_SESSION['user_type'], $_SESSION['email'], $permManager)) {
            showNotification(
                'success',
                'Success',
                'Paperwork deleted successfully',
                'The paperwork has been permanently deleted.',
                'dashboard.php'
            );
            exit;
        }

    } catch (Exception $e) {
        error_log("Delete error: " . $e->getMessage());
        showNotification(
            'error',
            'Error',
            'Error deleting paperwork',
            $e->getMessage(),
            'dashboard.php'
        );
        exit;
    }
}

// Set security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Include header after handling deletions and before any output
include 'includes/header.php';

// Load common data
$email = $_SESSION['email'];
$user_type = $_SESSION['user_type'];

// Update session timeout
$_SESSION['last_activity'] = time();

// Log session activity update
$last_activity = time();
$_SESSION['last_activity'] = $last_activity;
error_log("Session activity updated for {$email} at " . date('Y-m-d H:i:s', $last_activity));

// Define query based on user role with proper access control
$sqlloadpaperworks = match($user_type) {
    'hod' => [
        "SELECT p.*, u.name, u.email, u.department
         FROM tbl_ppw p 
         JOIN tbl_users u ON p.id = u.id 
         WHERE u.department = (
             SELECT department FROM tbl_users 
             WHERE email = ? AND user_type = 'hod'
         )
         AND (p.current_stage IN ('submitted', 'hod_review') 
              OR p.hod_approval IS NOT NULL)
         ORDER BY p.submission_time DESC",
        [$email]
    ],
    'dean' => [
        "SELECT p.*, u.name, u.email, u.department
         FROM tbl_ppw p 
         JOIN tbl_users u ON p.id = u.id 
         WHERE (p.current_stage = 'dean_review' 
                OR p.dean_approval IS NOT NULL
                OR p.hod_approval = 1)
         ORDER BY p.submission_time DESC",
        []
    ],
    'admin' => [
        "SELECT p.*, u.name, u.email, u.department
         FROM tbl_ppw p 
         JOIN tbl_users u ON p.id = u.id 
         ORDER BY p.submission_time DESC",
        []
    ],
    default => [ // This includes 'staff' and other user types
        "SELECT p.*, u.name, u.email, u.department
         FROM tbl_ppw p 
         JOIN tbl_users u ON p.id = u.id 
         WHERE p.user_email = ?
         ORDER BY p.submission_time DESC",
        [$_SESSION['email']] // Use session email to ensure proper filtering
    ]
};

// Execute query with proper parameters
$stmt = $conn->prepare($sqlloadpaperworks[0]); // Get SQL query
$stmt->execute($sqlloadpaperworks[1]); // Get parameters array
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination setup
$results_per_page = 10;
$total_results = count($rows);
$number_of_pages = ceil($total_results / $results_per_page);
$pageno = $_GET['pageno'] ?? 1;
$page_first_result = ($pageno - 1) * $results_per_page;
?>

<!-- Main Content -->
<main class="pt-4">
    <!-- Welcome Section -->
    <?php include "includes/dashboard/welcome.php"; ?>
    
    <!-- Stats Cards -->
    <?php include "includes/dashboard/stats.php"; ?>
    
    <!-- Quick Actions -->
    <?php if ($user_type !== 'admin'): ?>
        <?php include "includes/dashboard/quick_actions.php"; ?>
    <?php endif; ?>

    <!-- Search and Filter Section -->
    <?php include "includes/dashboard/searchnfilter.php"; ?>
    
    <!-- Paperworks Table -->
    <?php include "includes/dashboard/paperworks_table.php"; ?>
    
    <!-- Pagination -->
    <?php include "includes/dashboard/pagination.php"; ?>
</main>

<!-- Scripts are already included in footer.php -->
<?php include 'includes/footer.php'; ?>