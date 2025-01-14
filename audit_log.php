<?php
require_once 'telegram/telegram_handlers.php';
require_once 'includes/auth.php';  // Add this line to include Auth class
require_once 'includes/PermissionManager.php';

// Start session with strict settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Get Auth instance and validate session
$auth = Auth::getInstance();
$auth->validateSession();

// Verify user role level
if (!in_array($_SESSION['user_type'], ['dean', 'admin'])) {
    notifySystemError(
        'Access Violation',
        "Unauthorized access attempt to audit log by: {$_SESSION['email']}",
        __FILE__,
        __LINE__
    );
    header('Location: dashboard.php');
    exit;
}

// Get database connection from Auth
$conn = $auth->getConnection();

// Get PermissionManager from Auth
$permManager = $auth->getPermManager();

include 'includes/header.php';

// Get audit logs with pagination
try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $results_per_page = 10;
    $offset = ($page - 1) * $results_per_page;

    // Get total count
    $countStmt = $conn->query("SELECT COUNT(*) FROM tbl_audit_log");
    $total_records = $countStmt->fetchColumn();
    $total_pages = ceil($total_records / $results_per_page);

    // Get filtered logs with corrected column selection
    $sql = "SELECT 
        al.log_id,
        al.timestamp,
        al.action as action_type,  
        al.details,
        al.ip_address,
        u.name as actor_name,
        u.user_type,
        COALESCE(p.ref_number, 'N/A') as ref_number
    FROM tbl_audit_log al
    LEFT JOIN tbl_users u ON al.user_id = u.id
    LEFT JOIN tbl_ppw p ON al.action LIKE CONCAT('%', p.ref_number, '%')
    ORDER BY al.timestamp DESC
    LIMIT :offset, :limit";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Audit log error: " . $e->getMessage());
    notifySystemError(
        'Database Error',
        $e->getMessage(),
        __FILE__,
        __LINE__
    );
    die("An error occurred while retrieving audit logs.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - SOC PMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <main class="pt-5 mt-5">
        <div class="container py-4">
            <!-- Header Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="card-title h4 mb-3">
                        <i class="fas fa-history text-primary me-2"></i>
                        System Audit Log
                    </h2>
                    <p class="card-text text-muted mb-0">
                        View detailed system activity and paperwork changes.
                    </p>
                </div>
            </div>

            <!-- Export Buttons -->
            <div class="card border-0 shadow-sm mb-4 mt-3">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-download text-primary me-2"></i>
                            Export Options
                        </h5>
                        <div class="btn-group">
                            <a href="exports/export_audit_csv.php" class="btn btn-outline-primary">
                                <i class="fas fa-file-csv me-2"></i>Export to CSV
                            </a>
                            <a href="exports/export_audit_pdf.php" class="btn btn-outline-primary ms-2">
                                <i class="fas fa-file-pdf me-2"></i>Export to PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Audit Log Table -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">Timestamp</th>
                                    <th class="px-4 py-3">User</th>
                                    <th class="px-4 py-3">Action</th>
                                    <th class="px-4 py-3">Reference</th>
                                    <th class="px-4 py-3">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="px-4"><?php echo date('d M Y, h:i A', strtotime($log['timestamp'])); ?></td>
                                    <td class="px-4">
                                        <?php echo htmlspecialchars($log['actor_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($log['user_type']); ?></small>
                                    </td>
                                    <td class="px-4">
                                        <span class="badge <?php 
                                            echo match($log['action_type']) {
                                                'approve' => 'bg-success',
                                                'reject' => 'bg-danger',
                                                'create' => 'bg-primary',
                                                'modify' => 'bg-warning',
                                                default => 'bg-secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($log['action_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4"><?php echo htmlspecialchars($log['ref_number']); ?></td>
                                    <td class="px-4">
                                        <button type="button" 
                                                class="btn btn-sm btn-light" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#logModal_<?php echo $log['log_id']; ?>">
                                            <i class="fas fa-info-circle me-1"></i>View Details
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Audit log navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </main>

    <!-- Log Detail Modals -->
    <?php foreach ($logs as $log): ?>
    <div class="modal fade" id="logModal_<?php echo $log['log_id'] ?? ''; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Log Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-4">
                        <h6 class="fw-bold mb-2">Action Information</h6>
                        <p class="mb-1">
                            <span class="text-muted">Type:</span> 
                            <?php echo ucfirst($log['action_type'] ?? 'N/A'); ?>
                        </p>
                        <p class="mb-1">
                            <span class="text-muted">Time:</span>
                            <?php 
                                if (isset($log['timestamp'])) {
                                    $date = new DateTime($log['timestamp']);
                                    $date->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'));
                                    echo $date->format('d M Y, h:i:s A');
                                } else {
                                    echo 'N/A';
                                }
                            ?>
                        </p>
                        <p class="mb-0">
                            <span class="text-muted">IP Address:</span>
                            <?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    <?php if (!empty($log['details'])): ?>
                    <div class="mb-0">
                        <h6 class="fw-bold mb-2">Additional Details</h6>
                        <pre class="bg-light p-3 rounded mb-0"><code><?php echo htmlspecialchars($log['details']); ?></code></pre>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php include 'includes/footer.php'; ?>
</body>
</html>