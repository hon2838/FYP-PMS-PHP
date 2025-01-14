<?php
require_once 'includes/auth.php';
require_once 'includes/utilities.php';
require_once 'includes/audit_logger.php'; 
require_once 'telegram/telegram_handlers.php';

// Get auth instance and initialize session
$auth = Auth::getInstance();
$auth->validateSession();

// Get permission manager
$permManager = $auth->getPermManager();

// Load common data
$email = $_SESSION['email'];
$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

// Check permissions
try {
    $permManager->requirePermission('view_submissions');
} catch (Exception $e) {
    error_log("Permission denied: " . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}

// Get paperwork details
try {
    // Validate ppw_id parameter
    if (!isset($_GET['ppw_id'])) {
        throw new Exception("No paperwork ID provided");
    }
    
    $ppw_id = filter_input(INPUT_GET, 'ppw_id', FILTER_VALIDATE_INT);
    if (!$ppw_id) {
        throw new Exception("Invalid paperwork ID format");
    }

    // Log access attempt
    error_log("=== View Paperwork Access ===");
    error_log("Session State: " . json_encode([
        'email' => $email,
        'user_type' => $user_type,
        'user_id' => $user_id,
        'page' => basename($_SERVER['PHP_SELF']),
        'ppw_id' => $ppw_id
    ]));

    // Different queries based on user role
    $sql = match($user_type) {
        'admin' => "SELECT p.*, u.name as submitted_by, u.department 
                   FROM tbl_ppw p 
                   JOIN tbl_users u ON p.id = u.id 
                   WHERE p.ppw_id = ?",
        'hod' => "SELECT p.*, u.name as submitted_by, u.department 
                  FROM tbl_ppw p 
                  JOIN tbl_users u ON p.id = u.id 
                  WHERE p.ppw_id = ? 
                  AND u.department = (
                      SELECT department FROM tbl_users 
                      WHERE email = ? AND user_type = 'hod'
                  )",
        'dean' => "SELECT p.*, u.name as submitted_by, u.department 
                   FROM tbl_ppw p 
                   JOIN tbl_users u ON p.id = u.id 
                   WHERE p.ppw_id = ? 
                   AND (p.current_stage = 'dean_review' 
                       OR p.dean_approval IS NOT NULL 
                       OR p.current_stage = 'approved')",
        'staff' => "SELECT p.*, u.name as submitted_by, u.department 
                    FROM tbl_ppw p 
                    JOIN tbl_users u ON p.id = u.id 
                    WHERE p.ppw_id = ? 
                    AND p.user_email = ?",
        default => "SELECT p.*, u.name as submitted_by 
                   FROM tbl_ppw p 
                   JOIN tbl_users u ON p.id = u.id 
                   WHERE p.ppw_id = ? 
                   AND p.user_email = ?"
    };

    $stmt = $conn->prepare($sql);
    $params = match($user_type) {
        'admin' => [$ppw_id],
        'hod' => [$ppw_id, $email],
        'dean' => [$ppw_id],
        'staff' => [$ppw_id, $email],
        default => [$ppw_id, $email]
    };
    
    $stmt->execute($params);
    $paperwork = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$paperwork) {
        throw new Exception("Paperwork not found or access denied");
    }

    // Include header after all checks
    include 'includes/header.php';

} catch (Exception $e) {
    error_log("Paperwork view error: " . $e->getMessage());
    notifySystemError('Paperwork View Error', $e->getMessage(), __FILE__, __LINE__);
    echo "<script>
        alert('Error: " . htmlspecialchars($e->getMessage()) . "');
        window.location.href = 'dashboard.php';
    </script>";
    exit;
}

// Handle approval actions for admin/HOD/dean
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['ppw_id'])) {
    try {
        // Validate ppw_id
        $ppw_id = filter_var($_POST['ppw_id'], FILTER_VALIDATE_INT);
        if (!$ppw_id) {
            throw new Exception("Invalid paperwork ID");
        }

        // Check permissions
        $permManager->requirePermission('approve_submissions');

        // Process the approval action and get modal data
        $modalData = processApprovalAction($conn, $_POST, $user_type, $permManager);

        // Show success notification
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                NotificationModal.show({
                    type: '" . ($modalData['isApproval'] ? 'success' : 'warning') . "',
                    title: '" . ($modalData['isApproval'] ? 'Approved' : 'Returned for Modification') . "',
                    message: 'Paperwork " . $modalData['action'] . " successfully',
                    details: '" . addslashes($modalData['details']) . "',
                    redirectUrl: 'dashboard.php'
                });
            });
        </script>";
        exit;

    } catch (Exception $e) {
        error_log("Approval error: " . $e->getMessage());
        notifySystemError('Approval Error', $e->getMessage(), __FILE__, __LINE__);
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                NotificationModal.show({
                    type: 'error',
                    title: 'Error',
                    message: 'Error processing approval',
                    details: '" . addslashes($e->getMessage()) . "',
                    redirectUrl: 'dashboard.php'
                });
            });
        </script>";
        exit;
    }
}

function processApprovalAction($conn, $post, $user_type, $permManager) {
    // Validate inputs
    $ppw_id = filter_var($post['ppw_id'], FILTER_VALIDATE_INT);
    if (!$ppw_id) {
        throw new Exception("Invalid paperwork ID");
    }

    $note = isset($post['note']) ? 
        htmlspecialchars(trim($post['note']), ENT_QUOTES, 'UTF-8') : null;
    
    $current_time = date('Y-m-d H:i:s');
    
    try {
        // Get paperwork details first
        $stmt = $conn->prepare("SELECT p.*, u.department 
                                FROM tbl_ppw p 
                                JOIN tbl_users u ON p.id = u.id 
                                WHERE p.ppw_id = ?");
        $stmt->execute([$ppw_id]);
        $paperwork = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paperwork) {
            throw new Exception("Paperwork not found");
        }

        // For HOD approval, verify dean exists
        if ($user_type === 'hod' && $post['action'] === 'approve') {
            // Look for active dean
            $deanStmt = $conn->prepare(
                "SELECT id FROM tbl_users 
                 WHERE user_type = 'dean' 
                 AND active = 1 
                 LIMIT 1"
            );
            $deanStmt->execute();
            
            if (!$deanStmt->fetch()) {
                throw new Exception("No active dean found in the system");
            }
        }

        // Process based on user role
        switch($user_type) {
            case 'hod':
                if ($post['action'] === 'approve') {
                    $sql = "UPDATE tbl_ppw SET 
                            hod_approval = 1,
                            hod_note = ?,
                            hod_approval_date = ?,
                            current_stage = 'dean_review'
                            WHERE ppw_id = ? AND current_stage IN ('submitted', 'hod_review')";
                    $message = "Paperwork approved and forwarded to Dean";
                } else {
                    $sql = "UPDATE tbl_ppw SET 
                            hod_approval = 0,
                            hod_note = ?,
                            hod_approval_date = ?,
                            current_stage = 'returned',  
                            status = 0
                            WHERE ppw_id = ?";
                    $message = "Paperwork returned for modification";
                }
                break;
                
            case 'dean':
                if ($post['action'] === 'approve') {
                    $sql = "UPDATE tbl_ppw SET 
                            dean_approval = 1,
                            dean_note = ?,
                            dean_approval_date = ?,
                            current_stage = 'approved',
                            status = 1
                            WHERE ppw_id = ? AND hod_approval = 1 AND current_stage = 'dean_review'";
                    $message = "Paperwork approved";
                } else {
                    $sql = "UPDATE tbl_ppw SET 
                            dean_approval = 0,
                            dean_note = ?,
                            dean_approval_date = ?,
                            current_stage = 'returned',  
                            status = 0
                            WHERE ppw_id = ?";
                    $message = "Paperwork returned for modification";
                }
                break;

            case 'admin':
                if ($post['action'] === 'approve') {
                    $sql = "UPDATE tbl_ppw SET 
                            status = 1,
                            admin_note = ?,
                            admin_approval_date = ?,
                            current_stage = 'approved'
                            WHERE ppw_id = ?";
                    $message = "Paperwork approved by admin";
                } else {
                    $sql = "UPDATE tbl_ppw SET 
                            status = 0,
                            admin_note = ?,
                            admin_approval_date = ?,
                            current_stage = 'rejected'
                            WHERE ppw_id = ?";
                    $message = "Paperwork returned by admin";
                }
                break;

            default:
                throw new Exception("Invalid user role for approval");
        }

        // Prepare and execute statement
        $stmt = $conn->prepare($sql);
        if (!$stmt->execute([$note, $current_time, $ppw_id])) {
            throw new Exception("Error updating paperwork status: " . implode(" ", $stmt->errorInfo()));
        }

        // Log the action
        logAudit(
            $conn,
            $post['action'] === 'approve' ? 'APPROVE_PAPERWORK' : 'REJECT_PAPERWORK',
            "Paperwork ID: $ppw_id\n" .
            "Action by: {$user_type}\n" .
            "Note: $note\n" .
            "Reference: {$paperwork['ref_number']}"
        );

        if (!$post['action'] === 'approve') {
            // Send notification email to user
            sendReturnNotificationEmail(
                $paperwork['user_email'],
                $paperwork,
                $note,
                $user_type
            );
        }

        // Prepare data for the success modal
        $isApproval = $post['action'] === 'approve';
        return [
            'showSuccessModal' => true,
            'action' => $isApproval ? 'Approved' : 'Returned',
            'details' => $message,
            'reference' => $paperwork['ref_number'],
            'note' => $note,
            'isApproval' => $isApproval
        ];

    } catch (Exception $e) {
        throw $e; // Rethrow to be caught in the calling block
    }
}

/**
 * Get the appropriate color class for each paperwork status
 * @param string $status Current stage of the paperwork
 * @return string Bootstrap color class
 */
function getStatusColor($status) {
    return match($status) {
        'submitted' => 'secondary',
        'hod_review' => 'warning',
        'dean_review' => 'info',
        'approved' => 'success',
        'returned' => 'danger',
        'rejected' => 'danger',
        default => 'secondary'
    };
}

/**
 * Format the status text for display
 * @param string $status Current stage of the paperwork
 * @return string Formatted status text
 */
function formatStatus($status) {
    return match($status) {
        'submitted' => 'Submitted',
        'hod_review' => 'HOD Review',
        'dean_review' => 'Dean Review',
        'approved' => 'Approved',
        'returned' => 'Returned for Modification',
        'rejected' => 'Rejected',
        default => ucfirst($status)
    };
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Paperwork - SOC PMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body class="bg-light">
    <main class="pt-5 mt-5">
        <div class="container py-4">
            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-file-alt text-primary me-2"></i>
                        Paperwork Details
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active">View Paperwork</li>
                        </ol>
                    </nav>
                </div>
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm px-3">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>

            <!-- Main Content -->
            <div class="row g-4">
                <!-- Left Column - Main Details -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <!-- Status Badge -->
                            <div class="mb-4">
                                <span class="badge bg-<?php echo getStatusColor($paperwork['current_stage']); ?> px-3 py-2">
                                    <i class="fas fa-clock me-2"></i>
                                    <?php echo formatStatus($paperwork['current_stage']); ?>
                                </span>
                            </div>

                            <?php include "includes/paperwork/details.php"; ?>
                            
                            <!-- Document Preview Section -->
                            <div class="mt-4">
                                <?php include "includes/paperwork/document_section.php"; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Status & Actions -->
                <div class="col-lg-4">
                    <!-- Status Timeline Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-transparent border-0 py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history text-primary me-2"></i>
                                Approval Status
                            </h5>
                            
                        </div>
                        <div class="card-body p-4">
                            <?php include "includes/paperwork/status_section.php"; ?>
                        </div>
                    </div>

                    <!-- Approval Actions Card -->
                    <?php if ($user_type === 'dean' || $user_type === 'hod'): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 py-3">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-tasks text-primary me-2"></i>
                                Actions
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <?php include "includes/paperwork/approval_actions.php"; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Load Bootstrap JS first -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php 
    // Include modals
    include 'includes/modals/success.php';
    
    if (($user_type === 'dean' && $paperwork['current_stage'] === 'dean_review') || 
        ($user_type === 'hod' && in_array($paperwork['current_stage'], ['submitted', 'hod_review']))) {
        include "includes/paperwork/approval_modals.php";
    }
    ?>

    <?php 
    // Check if the success modal should be shown
    if (isset($modalData) && $modalData['showSuccessModal']): 
    ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                
                // Set modal styling based on action
                const modalHeader = document.querySelector('#successModal .modal-header');
                const statusIcon = document.getElementById('statusIcon');
                const noteSection = document.getElementById('noteSection');
                
                if (<?php echo json_encode($modalData['isApproval']); ?>) {
                    modalHeader.className = 'modal-header border-0 bg-success text-white';
                    statusIcon.className = 'fas fa-check-circle text-success animate__animated animate__bounceIn';
                    document.getElementById('successTitle').textContent = 'Success';
                } else {
                    modalHeader.className = 'modal-header border-0 bg-warning text-dark';
                    statusIcon.className = 'fas fa-undo text-warning animate__animated animate__bounceIn';
                    document.getElementById('successTitle').textContent = 'Returned for Modification';
                }
                
                // Set content
                document.getElementById('successMessage').textContent = <?php echo json_encode('Paperwork ' . $modalData['action']); ?>;
                document.getElementById('successDetails').textContent = <?php echo json_encode($modalData['details'] . "\nReference: " . $modalData['reference']); ?>;
                
                // Handle note section
                if (<?php echo !empty($modalData['note']) ? 'true' : 'false'; ?>) {
                    noteSection.classList.remove('d-none');
                    document.getElementById('noteText').textContent = <?php echo json_encode($modalData['note']); ?>;
                }
                
                // Add redirect handler
                document.getElementById('successModal').addEventListener('hidden.bs.modal', function () {
                    window.location.href = 'dashboard.php';
                });
                
                successModal.show();
            });
        </script>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
