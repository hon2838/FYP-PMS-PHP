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
    notifySystemError(
        'Permission Denied',
        "Unauthorized attempt to edit user by: {$auth->getUserEmail()}",
        __FILE__,
        __LINE__
    );
    header('Location: dashboard.php');
    exit;
}

// Get PermissionManager and database connection
$permManager = $auth->getPermManager();
$conn = $auth->getConnection();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate and sanitize input
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $user_type = htmlspecialchars(trim($_POST['user_type']), ENT_QUOTES, 'UTF-8');

        if (!$id || !$name || !$email || !$user_type) {
            throw new Exception("Invalid input parameters");
        }

        // Update user
        $stmt = $conn->prepare("UPDATE tbl_users SET name = ?, email = ?, user_type = ? WHERE id = ?");
        if (!$stmt->execute([$name, $email, $user_type, $id])) {
            throw new Exception("Failed to update user");
        }

        // Update user roles
        if (isset($_POST['roles'])) {
            // First delete existing roles
            $stmt = $conn->prepare("DELETE FROM tbl_user_roles WHERE user_id = ?");
            $stmt->execute([$id]);
            
            // Insert new roles
            $stmt = $conn->prepare("INSERT INTO tbl_user_roles (user_id, role_id, assigned_by) VALUES (?, ?, ?)");
            foreach ($_POST['roles'] as $role_id) {
                $stmt->execute([$id, $role_id, $auth->getUserId()]);
            }
        }

        // Notify admin about successful user modification
        notifySystemError(
            'User Modified',
            "User ID: $id modified by admin: {$auth->getUserEmail()}\nNew details: Name: $name, Email: $email, Type: $user_type",
            __FILE__,
            __LINE__
        );

        header('Location: admin_manage_account.php');
        exit();

    } catch (Exception $e) {
        error_log("User edit error: " . $e->getMessage());
        
        // Notify admin about error
        notifySystemError(
            'Database Error',
            $e->getMessage(),
            __FILE__,
            __LINE__
        );
        
        die("An error occurred while updating user. Please try again later.");
    }
}
?>