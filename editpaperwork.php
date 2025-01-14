<?php
require_once 'includes/auth.php';
require_once 'includes/utilities.php';
require_once 'includes/audit_logger.php'; 
require_once 'telegram/telegram_handlers.php';
include 'includes/modals/notification.php';

// Get Auth instance and validate session
$auth = Auth::getInstance();
$auth->validateSession();

// Get PermissionManager and database connection
$permManager = $auth->getPermManager();
$conn = $auth->getConnection();

// Verify paperwork ID exists
if (!isset($_GET['ppw_id'])) {
    error_log("No paperwork ID provided for editing");
    header('Location: dashboard.php?error=' . urlencode('No paperwork ID provided'));
    exit;
}

$ppw_id = filter_var($_GET['ppw_id'], FILTER_VALIDATE_INT);
if (!$ppw_id) {
    error_log("Invalid paperwork ID format");
    header('Location: dashboard.php?error=' . urlencode('Invalid paperwork ID'));
    exit;
}

try {
    // Check edit permission
    $permManager->requirePermission('edit_submission');  // Changed from 'edit_paperwork'

    // Get paperwork details with user info
    $stmt = $conn->prepare(
        "SELECT p.*, u.email, u.user_type, u.department 
         FROM tbl_ppw p 
         JOIN tbl_users u ON p.id = u.id 
         WHERE p.ppw_id = ?"
    );
    $stmt->execute([$ppw_id]);
    $paperwork = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paperwork) {
        throw new Exception("Paperwork not found");
    }

    // Update ownership verification logic
    $canEdit = false;
    switch($_SESSION['user_type']) {
        case 'admin':
            $canEdit = true;
            break;
        case 'staff':
            $canEdit = ($paperwork['user_email'] === $_SESSION['email'] && 
                       !in_array($paperwork['current_stage'], ['approved', 'dean_review', 'hod_review']));
            break;
        default:
            $canEdit = false;
    }

    if (!$canEdit) {
        throw new Exception("Unauthorized to edit this paperwork");
    }

    // Don't allow editing of approved paperwork
    if ($paperwork['status'] == 1) {
        showNotification(
            'error',
            'Access Denied',
            'Cannot edit approved paperwork',
            '',
            'dashboard.php'
        );
        exit;
    }

    // Add detailed logging
    error_log("=== Edit Paperwork Access ===");
    error_log("Session State: " . json_encode([
        'email' => $auth->getUserEmail(),
        'user_type' => $auth->getUserType(),
        'user_id' => $auth->getUserId(),
        'permissions' => $permManager->getPermissions(),
        'paperwork_owner' => $paperwork['user_email'],
        'current_stage' => $paperwork['current_stage']
    ]));

} catch (Exception $e) {
    error_log("Edit paperwork error: " . $e->getMessage());
    header('Location: dashboard.php?error=' . urlencode($e->getMessage()));
    exit;
}

// Rate limiting
if (!isset($_SESSION['edit_attempts'])) {
    $_SESSION['edit_attempts'] = 1;
    $_SESSION['edit_time'] = time();
} else {
    if (time() - $_SESSION['edit_time'] < 300) { // 5 minute window
        if ($_SESSION['edit_attempts'] > 5) { // Max 5 edits per 5 minutes
            error_log("Rate limit exceeded for user: " . $auth->getUserEmail());
            http_response_code(429);
            die("Too many requests. Please try again later.");
        }
        $_SESSION['edit_attempts']++;
    } else {
        $_SESSION['edit_attempts'] = 1;
        $_SESSION['edit_time'] = time();
    }
}

include 'includes/header.php';

try {
    // Sanitize and validate email
    $email = filter_var($auth->getUserEmail(), FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    $userQuery = "SELECT id, name, user_type FROM tbl_users WHERE email = ? AND active = 1";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->execute([$email]);
    
    if ($userStmt->rowCount() === 0) {
        throw new Exception("User not found or inactive.");
    }

    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    // Retrieve and handle paperwork details
    if (isset($_GET['ppw_id'])) {
        $ppw_id = $_GET['ppw_id'];
        // Allow admin to edit any paperwork, user can edit their own
        $sql = $user['user_type'] === 'admin' 
            ? "SELECT * FROM tbl_ppw WHERE ppw_id = ?" 
            : "SELECT * FROM tbl_ppw WHERE ppw_id = ? AND id = ?";
        
        $stmt = $conn->prepare($sql);
        $params = $user['user_type'] === 'admin' ? [$ppw_id] : [$ppw_id, $user['id']];
        $stmt->execute($params);
        $paperwork = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$paperwork) {
            echo "<script>alert('Paperwork not found or unauthorized.'); window.location.href='" . 
                 ($user['user_type'] === 'admin' ? 'dashboard.php' : 'dashboard.php') . 
                 "';</script>";
            exit;
        }

        // Check if paperwork is already approved
        if ($paperwork['status'] == 1) {
            echo "<script>alert('Cannot edit approved paperwork.'); window.location.href='" . 
                 ($user['user_type'] === 'admin' ? 'dashboard.php' : 'dashboard.php') . 
                 "';</script>";
            exit;
        }
    } else {
        header('Location: dashboard.php');
        exit;
    }

} catch (Exception $e) {
    error_log("Edit Paperwork error: " . $e->getMessage());
    notifySystemError(
        'Edit Paperwork Error',
        $e->getMessage(),
        __FILE__,
        __LINE__
    );
    die("An error occurred while editing paperwork.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // File upload handling
    $fileName = $paperwork['document_path']; // Keep existing file by default
    
    if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        $uploadDir = 'uploads/';
        $fileName = time() . '_' . basename($_FILES['document']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['document']['tmp_name'], $filePath)) {
            // Delete old file if exists
            if (!empty($paperwork['document_path'])) {
                @unlink($uploadDir . $paperwork['document_path']);
            }
        } else {
            echo "<script>alert('Error uploading file.');</script>";
            exit;
        }
    }

    try {
        // Update paperwork with notification
        $sql = "UPDATE tbl_ppw SET 
                ref_number = ?,
                project_name = ?,
                ppw_type = ?,
                session = ?,
                document_path = ?
                WHERE ppw_id = ? AND id = ?";
                
        $stmt = $conn->prepare($sql);
        if ($stmt->execute([
            $_POST['ref_number'],
            $_POST['project_name'],
            $_POST['ppw_type'],
            $_POST['session'],
            $fileName ?: $paperwork['document_path'],
            $_GET['ppw_id'],
            $user['id']
        ])) {
            showNotification(
                'success',
                'Success',
                'Paperwork updated successfully',
                'Your changes have been saved',
                'dashboard.php'
            );
            exit;
        } else {
            showNotification(
                'error',
                'Error',
                'Failed to update paperwork',
                'Please try again later',
                'dashboard.php'
            );
            exit;
        }

    } catch (Exception $e) {
        error_log("Paperwork update error: " . $e->getMessage());
        
        // Notify admin about error
        notifySystemError(
            'Database Error',
            $e->getMessage(),
            __FILE__,
            __LINE__
        );
        
        die("An error occurred while updating paperwork. Please try again later.");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Paperwork - SOC PMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <main class="pt-5 mt-5">
    <div class="container py-4">
        <!-- Modern Page Header -->
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h4 class="mb-0">
                        <i class="fas fa-edit text-primary me-2"></i>
                        Edit Paperwork
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mt-2">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">Edit Paperwork</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-auto">
                    <a href="dashboard.php" class="btn btn-outline-primary btn-sm px-3">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Modern Form Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <!-- Form Progress -->
                <div class="progress mb-4" style="height: 4px;">
                    <div class="progress-bar" role="progressbar" style="width: 0%" id="formProgress"></div>
                </div>

                <form action="editpaperwork.php?ppw_id=<?php echo htmlspecialchars($ppw_id); ?>" 
                      method="post" 
                      class="needs-validation" 
                      enctype="multipart/form-data" 
                      novalidate>

                    <div class="row g-4">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <!-- Basic Details Section -->
                            <div class="form-section mb-4">
                                <h5 class="form-section-title">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    Basic Information
                                </h5>
                                
                                <div class="card-body bg-light rounded p-4">
                                    <!-- Reference Number -->
                                    <div class="mb-4">
                                        <label class="form-label">Reference Number</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">
                                                <i class="fas fa-hashtag text-primary"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   value="<?php echo htmlspecialchars($paperwork['ref_number']); ?>"
                                                   readonly>
                                        </div>
                                    </div>

                                    <!-- Paperwork Name -->
                                    <div class="mb-4">
                                        <label class="form-label">Paperwork Title <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control" 
                                               name="project_name" 
                                               value="<?php echo htmlspecialchars($paperwork['project_name']); ?>"
                                               required>
                                    </div>

                                    <!-- Type Selection -->
                                    <div class="mb-4">
                                        <label class="form-label">Document Type <span class="text-danger">*</span></label>
                                        <select class="form-select" name="ppw_type" required>
                                            <option value="">Select type...</option>
                                            <?php
                                            $types = ['Project Proposal', 'Research Paper', 'Technical Report', 'Documentation', 'Other'];
                                            foreach ($types as $type) {
                                                $selected = ($paperwork['ppw_type'] == $type) ? 'selected' : '';
                                                echo "<option value=\"$type\" $selected>$type</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <!-- Timeline Section -->
                            <div class="form-section mb-4">
                                <h5 class="form-section-title">
                                    <i class="fas fa-calendar text-primary me-2"></i>
                                    Timeline Details
                                </h5>
                                
                                <div class="card-body bg-light rounded p-4">
                                    <!-- Session -->
                                    <div class="mb-4">
                                        <label class="form-label">Academic Session <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control" 
                                               name="session" 
                                               value="<?php echo htmlspecialchars($paperwork['session']); ?>"
                                               required>
                                    </div>

                                    <!-- Document Upload -->
                                    <div class="mb-4">
                                        <label class="form-label">Document</label>
                                        <?php if (!empty($paperwork['document_path'])): ?>
                                            <div class="current-file mb-3">
                                                <div class="d-flex align-items-center p-3 bg-white rounded border">
                                                    <i class="fas fa-file-alt text-primary me-3 fa-2x"></i>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0">Current Document</h6>
                                                        <p class="small text-muted mb-0">
                                                            <?php echo htmlspecialchars(substr($paperwork['document_path'], strpos($paperwork['document_path'], '_') + 1)); ?>
                                                        </p>
                                                    </div>
                                                    <a href="uploads/<?php echo htmlspecialchars($paperwork['document_path']); ?>" 
                                                       class="btn btn-outline-primary btn-sm" 
                                                       target="_blank">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="upload-area p-4 bg-white rounded border border-dashed text-center">
                                            <i class="fas fa-cloud-upload-alt fa-2x text-primary mb-3"></i>
                                            <input type="file" 
                                                   class="form-control" 
                                                   id="document" 
                                                   name="document" 
                                                   accept=".pdf,.doc,.docx">
                                            <p class="small text-muted mt-2 mb-0">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Upload new document (PDF, DOC, DOCX)
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" 
                                class="btn btn-light px-4" 
                                onclick="window.location.href='dashboard.php'">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
    <?php include 'includes/footer.php'; ?>                                
    <!-- Form Validation Script -->
    <script>
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
    
</body>
</html>