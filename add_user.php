<?php
require_once 'includes/auth.php';
require_once 'includes/utilities.php';

// Get Auth instance and validate session
$auth = Auth::getInstance();
$auth->validateSession();

// Enforce 'manage_users' permission
try {
    $auth->requirePermission('manage_users');
} catch (Exception $e) {
    error_log("Permission denied: " . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}

// Get PermissionManager and database connection
$permManager = $auth->getPermManager();
$conn = $auth->getConnection();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate inputs
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user_type = $_POST['user_type'];

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Prepare SQL statement
        $sql = "INSERT INTO tbl_users (name, email, password, user_type) 
                VALUES (:name, :email, :password, :user_type)";
        $stmt = $conn->prepare($sql);
        
        // Bind parameters and execute
        if ($stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => $password,
            ':user_type' => $user_type
        ])) {
            // Notify admin about successful user addition
            notifySystemError(
                'User Added',
                "New user added by admin {$auth->getUserEmail()}\n" .
                "Name: $name\n" .
                "Email: $email\n" .
                "Type: $user_type",
                __FILE__,
                __LINE__
            );
            
            header('Location: admin_manage_account.php');
            exit;
        }

    } catch (Exception $e) {
        error_log("User addition error: " . $e->getMessage());
        
        // Notify admin about error
        notifySystemError(
            'Database Error',
            $e->getMessage(),
            __FILE__,
            __LINE__
        );
        
        die("An error occurred while adding user. Please try again later.");
    }
}
?>