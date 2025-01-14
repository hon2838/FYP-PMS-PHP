<?php
require_once 'includes/auth.php';
require_once 'includes/utilities.php';
require_once 'includes/audit_logger.php'; 
require_once 'telegram/telegram_handlers.php';
include 'includes/modals/notification.php';

// Get Auth instance and validate session
$auth = Auth::getInstance();
$auth->validateSession();

// Get PermissionManager
$permManager = $auth->getPermManager();

// Only allow admin, HOD and staff to create paperwork
if (!in_array($_SESSION['user_type'], ['admin', 'staff'])) {
    error_log("Permission denied: User type {$_SESSION['user_type']} cannot create paperwork");
    header('Location: dashboard.php');
    exit;
}

try {
    $permManager->requirePermission('create_submission');
} catch (Exception $e) {
    error_log("Permission denied: " . $e->getMessage());
    notifySystemError(
        'Permission Denied',
        "User {$auth->getUserEmail()} attempted to create paperwork without permission",
        __FILE__,
        __LINE__
    );
    header('Location: dashboard.php');
    exit;
}

// Include header after setting up permManager
include 'includes/header.php';

// Get PermissionManager and database connection
$conn = $auth->getConnection();

// Add debug logging
error_log("=== Create Paperwork Access ===");
error_log("Session State: " . json_encode([
    'email' => $auth->getUserEmail(),
    'user_type' => $auth->getUserType(),
    'user_id' => $auth->getUserId(),
    'permissions' => $permManager->getPermissions(),
    'page' => basename($_SERVER['PHP_SELF'])
]));

// Rate limiting
if (!isset($_SESSION['paperwork_submissions'])) {
    $_SESSION['paperwork_submissions'] = 1;
    $_SESSION['submission_time'] = time();
} else {
    if (time() - $_SESSION['submission_time'] < 300) { // 5 minute window
        if ($_SESSION['paperwork_submissions'] > 5) { // Max 5 submissions
            error_log("Rate limit exceeded for user: " . $auth->getUserEmail());
            http_response_code(429);
            die("Too many submissions. Please try again later.");
        }
        $_SESSION['paperwork_submissions']++;
    } else {
        $_SESSION['paperwork_submissions'] = 1;
        $_SESSION['submission_time'] = time();
    }
}



/**
 * Validates file uploads for paperwork
 * 
 * @param array $file $_FILES array element
 * @return bool Returns true if file is valid
 * @throws Exception If validation fails
 */
function validateFileUpload($file) {
    // Increase maximum file size to 20MB
    $maxSize = 20 * 1024 * 1024; 
    
    // Expand allowed MIME types
    $allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/x-pdf',
        'binary/octet-stream', // Some PDF scanners use this
        'application/octet-stream' // Some systems use this for PDFs
    ];

    // Basic file checks with improved error messages
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid file upload parameters');
    }

    // More informative upload error handling
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
            throw new Exception('File exceeds PHP maximum file size limit. Maximum allowed is 20MB');
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception('File exceeds form maximum file size limit');
        case UPLOAD_ERR_PARTIAL:
            throw new Exception('File was only partially uploaded - please try again');
        case UPLOAD_ERR_NO_FILE:
            throw new Exception('No file was uploaded - please select a file');
        case UPLOAD_ERR_NO_TMP_DIR:
            throw new Exception('Missing temporary folder - contact system administrator');
        case UPLOAD_ERR_CANT_WRITE:
            throw new Exception('Failed to write file - check folder permissions');
        default:
            throw new Exception('Unknown upload error - please try again');
    }

    // Size validation with clear message
    if ($file['size'] > $maxSize) {
        throw new Exception('File is too large. Maximum size is ' . ($maxSize/1024/1024) . 'MB');
    }

    // More flexible MIME type validation
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    // Check file extension as backup
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx'];

    if (!in_array($mimeType, $allowedTypes, true) && !in_array($extension, $allowedExtensions)) {
        throw new Exception('Invalid file type. Only PDF, DOC and DOCX files are allowed. Detected type: ' . $mimeType);
    }

    return true;
}

/**
 * Generates the next sequential reference number
 * @param PDO $conn Database connection
 * @return string Next reference number in format PPW/YYYY/XXXX
 */
function generateReferenceNumber($conn) {
    $year = date('Y');
    
    // Get the last reference number for this year
    $stmt = $conn->prepare("
        SELECT ref_number 
        FROM tbl_ppw 
        WHERE ref_number LIKE :pattern 
        ORDER BY ref_number DESC 
        LIMIT 1
    ");
    
    $pattern = "PPW/{$year}/%";
    $stmt->execute(['pattern' => $pattern]);
    $lastRef = $stmt->fetch(PDO::FETCH_COLUMN);
    
    if ($lastRef) {
        // Extract the sequence number and increment
        $sequence = (int)substr($lastRef, -4);
        $nextSequence = $sequence + 1;
    } else {
        // Start with 1 if no existing reference numbers
        $nextSequence = 1;
    }
    
    // Format: PPW/2025/0001
    return sprintf("PPW/%d/%04d", $year, $nextSequence);
}

// Get user details
$email = $_SESSION['email'];
$userQuery = "SELECT id, name, user_type FROM tbl_users WHERE email = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->execute([$email]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Define upload directory with absolute path
    $uploadDir = __DIR__ . '/uploads/';

    // Create directory if it doesn't exist with proper permissions
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true)) {
            error_log("Failed to create upload directory: $uploadDir");
            throw new Exception('Upload directory creation failed');
        }
        chmod($uploadDir, 0775);
    }

    // Add more detailed logging for file operations
    error_log("Upload directory: $uploadDir");
    error_log("Upload directory exists: " . (file_exists($uploadDir) ? 'yes' : 'no'));
    error_log("Upload directory writable: " . (is_writable($uploadDir) ? 'yes' : 'no'));

    $fileName = '';
    if (isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
        try {
            validateFileUpload($_FILES['document']);
            $fileName = time() . '_' . basename($_FILES['document']['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['document']['tmp_name'], $filePath)) {
                // Success handling
            } else {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        NotificationModal.show({
                            type: 'error',
                            title: 'Upload Error',
                            message: 'Failed to upload document',
                            details: 'Please try again with a different file'
                        });
                    });
                </script>";
                exit;
            }
        } catch (Exception $e) {
            echo "<script>alert('" . $e->getMessage() . "');</script>";
            exit;
        }
    }

    // Insert into tbl_ppw
    try {
        $conn->beginTransaction();
        
        // Generate reference number inside transaction
        $refNumber = generateReferenceNumber($conn);
        
        // Update the INSERT query to include current_stage and status
        $sql = "INSERT INTO tbl_ppw (
            id, 
            name, 
            session, 
            project_name, 
            ref_number, 
            ppw_type, 
            project_date, 
            document_path, 
            user_email,
            current_stage, // Add these
            status,
            submission_time
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 
            CURRENT_DATE(), 
            ?, ?,
            'hod_review', // Set initial stage to hod_review
            '0', // Set initial status
            CURRENT_TIMESTAMP
        )";
        
        $stmt = $conn->prepare($sql);
        
        try {
            $result = $stmt->execute([
                $user['id'],
                $user['name'],
                $_POST['session'],
                $_POST['project_name'],
                $refNumber,
                $_POST['ppw_type'],
                $fileName,
                $_SESSION['email']
            ]);
            
            $ppw_id = $conn->lastInsertId();
            $conn->commit();
            
            // Log creation
            logAudit(
                $conn,
                'SUBMIT_PAPERWORK',
                "Submitted paperwork\n" .
                "ID: $ppw_id\n" .
                "Reference: {$refNumber}\n" .
                "Type: {$_POST['ppw_type']}\n" .
                "Stage: hod_review"
            );

            // Notify HOD via email
            try {
                // Get HOD email from the same department
                $hodStmt = $conn->prepare(
                    "SELECT email FROM tbl_users 
                     WHERE user_type = 'hod' 
                     AND active = 1 
                     AND department = (
                         SELECT department FROM tbl_users WHERE email = ?
                     )"
                );
                $hodStmt->execute([$_SESSION['email']]);
                $hodEmail = $hodStmt->fetchColumn();
                
                if ($hodEmail) {
                    sendHODNotificationEmail($hodEmail, [
                        'ref_number' => $refNumber,
                        'project_name' => $_POST['project_name']
                    ]);
                }
            } catch (Exception $e) {
                error_log("HOD notification error: " . $e->getMessage());
            }

            // Show success notification
            showNotification(
                'success',
                'Success',
                'Paperwork submitted successfully',
                'Reference: ' . htmlspecialchars($refNumber) . "\nStatus: Pending HOD Review",
                'dashboard.php'
            );
            exit;
            
        } catch (PDOException $e) {
            $conn->rollBack();
            
            if ($e->getCode() == '23000') { // Duplicate entry error
                // Generate a new reference number and retry once
                $refNumber = generateReferenceNumber($conn);
                $stmt->execute([
                    $user['id'],
                    $user['name'],
                    $_POST['session'],
                    $_POST['project_name'],
                    $refNumber,
                    $_POST['ppw_type'],
                    $fileName,
                    $_SESSION['email']
                ]);
                $conn->commit();
            } else {
                throw $e;
            }
        }
        
    } catch (Exception $e) {
        // Log exception and notify admin
        error_log("Create paperwork error: " . $e->getMessage());
        notifySystemError("System Error", $e->getMessage(), __FILE__, __LINE__);
        
        showNotification(
            'error',
            'Error',
            'Failed to create paperwork',
            'Please try again later',
            'dashboard.php'
        );
        exit;
    }

}
?>

<main class="pt-5 mt-5">
    <div class="container py-4">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="mb-0">
                <i class="fas fa-file-alt text-primary me-2"></i>
                Create New Paperwork
            </h4>
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <!-- Modern Form Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <!-- Progress Indicator -->
                <div class="progress mb-4" style="height: 4px;">
                    <div class="progress-bar" role="progressbar" style="width: 0%" id="formProgress"></div>
                </div>

                <form action="create_paperwork.php" method="post" class="needs-validation" enctype="multipart/form-data" novalidate>
                    <!-- Form Sections -->
                    <div class="row g-4">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <!-- Basic Details Section -->
                            <div class="form-section mb-4">
                                <h5 class="form-section-title mb-3">Basic Details</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Reference Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">
                                            <i class="fas fa-hashtag"></i>
                                        </span>
                                        <input type="text" 
                                               class="form-control" 
                                               value="<?php echo htmlspecialchars(generateReferenceNumber($conn)); ?>"
                                               readonly>
                                    </div>
                                    <small class="text-muted">Auto-generated reference number</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Paperwork Name <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="project_name" 
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select class="form-select" name="ppw_type" required>
                                        <option value="">Select type...</option>
                                        <option value="Project Proposal">Project Proposal</option>
                                        <option value="Research Paper">Research Paper</option>
                                        <option value="Technical Report">Technical Report</option>
                                        <option value="Documentation">Documentation</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <!-- Timeline Section -->
                            <div class="form-section mb-4">
                                <h5 class="form-section-title mb-3">Timeline</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Session <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           name="session" 
                                           placeholder="e.g., 2024/2025" 
                                           required>
                                </div>

                                <div class="row g-3">
                                    <div class="col-6">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" class="form-control" name="startdate" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">End Date</label>
                                        <input type="date" class="form-control" name="end_date" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Full Width Sections -->
                        <div class="col-12">
                            <!-- Document Upload Section -->
                            <div class="form-section mb-4">
                                <h5 class="form-section-title mb-3">Document Upload</h5>
                                
                                <div class="upload-area p-4 bg-light rounded text-center">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                    <input type="file" 
                                           class="form-control" 
                                           id="document" 
                                           name="document" 
                                           accept=".pdf,.doc,.docx" 
                                           required>
                                    <small class="d-block text-muted mt-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Accepted formats: PDF, DOC, DOCX (Max 20MB)
                                    </small>
                                </div>
                            </div>

                            <!-- Details Section -->
                            <div class="form-section mb-4">
                                <h5 class="form-section-title mb-3">Project Details</h5>
                                
                                <div class="mb-3">
                                    <label class="form-label">Background</label>
                                    <textarea class="form-control" 
                                              name="background" 
                                              rows="4" 
                                              required></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Aim</label>
                                    <textarea class="form-control" 
                                              name="aim" 
                                              rows="4" 
                                              required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-light" onclick="window.location.href='dashboard.php'">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Paperwork
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
// Your existing form validation script
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
                
                NotificationModal.show({
                    type: 'error',
                    title: 'Form Validation Error',
                    message: 'Please check all required fields',
                    details: 'Ensure all required fields are filled correctly.'
                });
            }
            
            // Additional file validation
            const fileInput = form.querySelector('#document');
            if (!validateFileUpload(fileInput)) {
                event.preventDefault();
                event.stopPropagation();
                return false;
            }
            
            form.classList.add('was-validated')
        }, false)
    })
})()

// File validation function
function validateFileUpload(fileInput) {
    const allowedTypes = [
        'application/pdf',
        'application/x-pdf',
        'application/acrobat',
        'applications/vnd.pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/octet-stream',  // Some systems use this for PDFs
        'binary/octet-stream'       // Some PDF scanners use this
    ];
    const maxSize = 20; // MB
    
    if (!fileInput.files.length) {
        NotificationModal.show({
            type: 'error',
            title: 'File Required',
            message: 'Please select a file to upload.',
            details: 'You must upload a PDF or Word document.'
        });
        return false;
    }

    const file = fileInput.files[0];
    const fileSize = file.size / 1024 / 1024;
    const extension = file.name.split('.').pop().toLowerCase();
    
    // Check both MIME type and extension
    if (!allowedTypes.includes(file.type) && !['pdf', 'doc', 'docx'].includes(extension)) {
        NotificationModal.show({
            type: 'error',
            title: 'Invalid File Type',
            message: 'Please upload a valid document.',
            details: `Allowed formats: PDF, DOC, DOCX\nSelected type: ${file.type}\nExtension: ${extension}`
        });
        fileInput.value = '';
        return false;
    }
    
    if (fileSize > maxSize) {
        NotificationModal.show({
            type: 'warning',
            title: 'File Too Large',
            message: `Maximum file size is ${maxSize}MB.`,
            details: `Your file size: ${fileSize.toFixed(2)}MB.`
        });
        fileInput.value = '';
        return false;
    }
    
    return true;
}

// Add real-time file validation on change
document.querySelector('#document').addEventListener('change', function(event) {
    validateFileUpload(this);
});

// Form progress tracking
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const progress = document.querySelector('#formProgress');
    const requiredFields = form.querySelectorAll('[required]');
    const totalFields = requiredFields.length;

    function updateProgress() {
        const filledFields = Array.from(requiredFields).filter(field => {
            if (field.type === 'file') {
                return field.files.length > 0;
            }
            return field.value.trim() !== '';
        }).length;

        const percentage = (filledFields / totalFields) * 100;
        progress.style.width = `${percentage}%`;
    }

    // Update progress on input
    form.addEventListener('input', updateProgress);
    form.addEventListener('change', updateProgress);
});
</script>