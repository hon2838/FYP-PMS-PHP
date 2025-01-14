<?php
require_once 'vendor/autoload.php';
require_once 'includes/auth.php';
require_once 'includes/utilities.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Required environment variables
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

// Set JSON response header
header('Content-Type: application/json');

try {
    // Get Auth instance and validate session
    $auth = Auth::getInstance();
    $auth->validateSession();

    // Get database connection
    $conn = $auth->getConnection();

    // Validate and sanitize input with strict typing
    $settings = [
        'email_notifications' => (bool)isset($_POST['email_notifications']),
        'browser_notifications' => (bool)isset($_POST['browser_notifications']),
        'theme' => in_array($_POST['theme'], ['light', 'dark', 'system'], true) ? 
                  htmlspecialchars($_POST['theme']) : 'light',
        'compact_view' => (bool)isset($_POST['compact_view'])
    ];

    // Store settings in session
    $_SESSION['settings'] = $settings;

    // Update database using prepared statement
    $stmt = $conn->prepare(
        "UPDATE tbl_users SET settings = ? WHERE id = ?"
    );
    
    $success = $stmt->execute([json_encode($settings), $_SESSION['user_id']]);

    if (!$success) {
        throw new Exception("Failed to update settings");
    }

    // Send success response
    echo json_encode([
        'success' => true,
        'theme' => $settings['theme'],
        'settings' => $settings
    ]);
    exit;

} catch (Exception $e) {
    // Log error
    error_log("Settings update error: " . $e->getMessage());
    
    // Send error response
    echo json_encode([
        'success' => false,
        'error' => 'Error updating settings'
    ]);
    exit;
}
