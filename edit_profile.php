<?php
require_once 'includes/auth.php';
require_once 'includes/audit_logger.php'; 
require_once 'includes/utilities.php'; // Ensure utilities.php contains the showNotification function

// Get Auth instance and validate session
$auth = Auth::getInstance();
$auth->validateSession();

// Get PermissionManager and database connection
$permManager = $auth->getPermManager();
$conn = $auth->getConnection();

include 'includes/header.php';
include 'includes/modals/notification.php'; // Include modal template first

try {
    // Get user data
    $stmt = $conn->prepare("SELECT * FROM tbl_users WHERE email = ?");
    $stmt->execute([$auth->getUserEmail()]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'];

        switch ($action) {
            case 'update_name':
                // Update name only
                $name = trim($_POST['name']);
                if (empty($name)) {
                    throw new Exception("Name cannot be empty");
                }

                $stmt = $conn->prepare("UPDATE tbl_users SET name = ? WHERE email = ?");
                if ($stmt->execute([$name, $auth->getUserEmail()])) {
                    logAudit($conn, 'PROFILE_UPDATE', "User updated their name to: $name");
                    $_SESSION['name'] = $name; // Update session name
                    showNotification(
                        'success',
                        'Success',
                        'Name updated successfully',
                        '',
                        'dashboard.php'
                    );
                    exit;
                }
                break;

            case 'update_password':
                // Update password only
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                // Validate current password
                if (!password_verify($current_password, $user['password'])) {
                    throw new Exception("Current password is incorrect");
                }

                // Validate new password
                if (empty($new_password)) {
                    throw new Exception("New password cannot be empty");
                }
                if ($new_password !== $confirm_password) {
                    throw new Exception("New passwords do not match");
                }
                if (strlen($new_password) < 6) {
                    throw new Exception("Password must be at least 6 characters");
                }

                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE tbl_users SET password = ? WHERE email = ?");
                if ($stmt->execute([$hashed_password, $auth->getUserEmail()])) {
                    logAudit($conn, 'PASSWORD_UPDATE', "User updated their password");
                    showNotification(
                        'success',
                        'Success',
                        'Password updated successfully',
                        'Your password has been changed',
                        'dashboard.php'
                    );
                    exit;
                }
                break;
        }
    }

} catch (Exception $e) {
    error_log("Profile update error: " . $e->getMessage());
    showNotification(
        'error',
        'Error',
        'Update failed',
        $e->getMessage(),
        'dashboard.php'
    );
    exit;
}
?>

<!-- Main Content -->
<main class="pt-5 mt-5">
    <div class="container py-5">
        <!-- Header -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="card-title h4 mb-3">
                    <i class="fas fa-user-edit text-primary me-2"></i>
                    Edit Profile
                </h2>
                <p class="card-text text-muted mb-0">
                    Update your account information below
                </p>
            </div>
        </div>

        <div class="row g-4 mt-3">
            <!-- Update Name Form -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-user text-primary me-2"></i>
                            Update Name
                        </h5>
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="update_name">
                            <div class="mb-4">
                                <label class="form-label">Full Name</label>
                                <input type="text" 
                                       class="form-control form-control-lg shadow-sm" 
                                       name="name" 
                                       value="<?php echo htmlspecialchars($user['name']); ?>" 
                                       required>
                                <div class="invalid-feedback">
                                    Please enter your name
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-2"></i>Update Name
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Update Password Form -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-key text-primary me-2"></i>
                            Change Password
                        </h5>
                        <form method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="update_password">
                            <div class="mb-4">
                                <label class="form-label">Current Password</label>
                                <input type="password" 
                                       class="form-control form-control-lg shadow-sm" 
                                       name="current_password" 
                                       required>
                                <div class="invalid-feedback">
                                    Please enter your current password
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">New Password</label>
                                <input type="password" 
                                       class="form-control form-control-lg shadow-sm" 
                                       name="new_password"
                                       minlength="6" 
                                       required>
                                <div class="invalid-feedback">
                                    Password must be at least 6 characters
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" 
                                       class="form-control form-control-lg shadow-sm" 
                                       name="confirm_password"
                                       required>
                                <div class="invalid-feedback">
                                    Please confirm your new password
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-key me-2"></i>Update Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Include notification modal template first -->
<?php include 'includes/modals/notification.php'; ?>

<!-- Then your custom scripts -->
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

            // For password form, check if passwords match
            if (form.querySelector('[name="new_password"]')) {
                const newPassword = form.querySelector('[name="new_password"]').value;
                const confirmPassword = form.querySelector('[name="confirm_password"]').value;
                if (newPassword !== confirmPassword) {
                    event.preventDefault();
                    event.stopPropagation();
                    // Use try-catch to handle potential undefined NotificationModal
                    try {
                        NotificationModal.show({
                            type: 'error',
                            title: 'Error',
                            message: 'Passwords do not match',
                            details: 'Please make sure your new password and confirmation password match.'
                        });
                    } catch (e) {
                        console.error('NotificationModal not initialized:', e);
                        alert('Passwords do not match');
                    }
                }
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>