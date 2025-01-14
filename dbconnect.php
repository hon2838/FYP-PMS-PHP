<?php
// dbconnect.php

// Define database credentials as constants
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'soc_pms');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'r1');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'db_errors.log');

try {
    // Construct DSN with charset
    $dsn = "mysql:host=" . DB_HOST . 
           ";dbname=" . DB_NAME . 
           ";charset=" . DB_CHARSET;
    
    // Array of PDO options for security
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_TIMEOUT => 5, // Connection timeout in seconds
    ];

    // Create PDO instance
    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Additional security checks
    $conn->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
    $conn->exec("SET SESSION time_zone = '+00:00'");
    $conn->exec("SET time_zone = '+08:00'");
    
    // Verify connection is alive
    if (!$conn->query('SELECT 1')) {
        throw new PDOException('Database connection test failed');
    }

    // Add after database connection
    require_once 'telegram/telegram_bot.php';

    $telegram = new TelegramBot(
        getenv('TELEGRAM_BOT_TOKEN') ?: 'your_bot_token_here',
        getenv('TELEGRAM_CHAT_ID') ?: 'your_chat_id_here'
    );

} catch(PDOException $e) {
    // Log error securely
    error_log("Database connection error: " . $e->getMessage());
    
    // Generic error message for users
    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Temporarily Unavailable');
    die('Database connection error. Please try again later.');
}

// Function to sanitize database inputs - only declare if not exists
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map('sanitizeInput', $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}

// Function to validate database connection - only declare if not exists
if (!function_exists('validateConnection')) {
    function validateConnection($conn) {
        try {
            $conn->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            error_log("Connection validation failed: " . $e->getMessage());
            return false;
        }
    }
}

// Function to close database connection - only declare if not exists
if (!function_exists('closeConnection')) {
    function closeConnection(&$conn) {
        $conn = null;
    }
}

// Register shutdown function
register_shutdown_function(function() use (&$conn) {
    closeConnection($conn);
});
?>
