<?php
// Get user's ID and department from session
$user_id = $_SESSION['user_id'] ?? null;
$user_type = $_SESSION['user_type'] ?? '';

// Get user's department from database
if ($user_type === 'hod') {
    $deptQuery = $conn->prepare("SELECT department FROM tbl_users WHERE id = ? AND user_type = 'hod'");
    $deptQuery->execute([$user_id]);
    $department = $deptQuery->fetchColumn();
} else {
    $department = null;
}

// Get current user role and permissions
$can_create = $permManager->hasPermission('create_submission');
$can_manage_account = true; // All users can manage their account
?>

<div class="container mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h5 class="card-title mb-3">
                <i class="fas fa-bolt text-primary me-2"></i>
                Quick Actions
            </h5>
            <div class="d-flex flex-wrap gap-2">
                <!-- Quick Actions Section -->
                <?php if ($user_type === 'admin'): ?>
                    <a href="admin_manage_account.php" class="btn btn-primary">
                        <i class="fas fa-users-cog me-2"></i>Manage Users
                    </a>
                    <a href="audit_log.php" class="btn btn-outline-primary">
                        <i class="fas fa-history me-2"></i>Audit Log
                    </a>
                    <a href="create_paperwork.php" class="btn btn-outline-primary">
                        <i class="fas fa-plus me-2"></i>New Paperwork
                    </a>
                <?php elseif ($user_type === 'hod'): ?>
                    <a href="create_paperwork.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>New Paperwork
                    </a>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#departmentStatsModal">
                        <i class="fas fa-chart-bar me-2"></i>Department Stats
                    </button>
                <?php elseif ($user_type === 'dean'): ?>
                    <a href="audit_log.php" class="btn btn-outline-primary">
                        <i class="fas fa-history me-2"></i>Audit Log
                    </a>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#approvalStatsModal">
                        <i class="fas fa-chart-line me-2"></i>Approval Stats
                    </button>
                <?php endif; ?>
                
                <!-- Common actions for all users -->
                <a href="edit_profile.php" class="btn btn-outline-primary" role="button">
                    <i class="fas fa-user-cog me-2"></i>Account Settings
                </a>
            </div>
        </div>
    </div>
</div>

<?php if ($user_type === 'hod'): ?>
<div class="modal fade" id="departmentStatsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-chart-bar text-primary me-2"></i>
                    Department Statistics
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <?php
                // Get user's department from database instead of session
                if ($user_type === 'hod') {
                    $deptQuery = $conn->prepare("SELECT department FROM tbl_users WHERE id = ? AND user_type = 'hod'");
                    $deptQuery->execute([$user_id]);
                    $userDept = $deptQuery->fetchColumn();

                    // Get department stats
                    $dept_stats = [
                        'total' => count(array_filter($rows, fn($row) => $row['department'] === $userDept)),
                        'pending' => count(array_filter($rows, fn($row) => 
                            $row['department'] === $userDept && 
                            $row['current_stage'] === 'hod_review'
                        )),
                        'approved' => count(array_filter($rows, fn($row) => 
                            $row['department'] === $userDept && 
                            $row['hod_approval'] === 1
                        )),
                        'returned' => count(array_filter($rows, fn($row) => 
                            $row['department'] === $userDept && 
                            $row['hod_approval'] === 0
                        ))
                    ];
                }
                ?>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-file-alt text-primary me-2"></i>Total Submissions</span>
                        <span class="badge bg-primary rounded-pill"><?php echo $dept_stats['total']; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-clock text-warning me-2"></i>Pending Review</span>
                        <span class="badge bg-warning rounded-pill"><?php echo $dept_stats['pending']; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-check-circle text-success me-2"></i>Approved</span>
                        <span class="badge bg-success rounded-pill"><?php echo $dept_stats['approved']; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-undo text-danger me-2"></i>Returned</span>
                        <span class="badge bg-danger rounded-pill"><?php echo $dept_stats['returned']; ?></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Approval Stats Modal for Dean -->
<?php if ($user_type === 'dean'): ?>
<div class="modal fade" id="approvalStatsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    Approval Statistics
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <?php
                // Calculate approval statistics
                $stats = [
                    'total' => count($rows),
                    'pending' => count(array_filter($rows, fn($row) => 
                        $row['current_stage'] === 'dean_review'
                    )),
                    'approved' => count(array_filter($rows, fn($row) => 
                        isset($row['dean_approval']) && $row['dean_approval'] === 1
                    )),
                    'returned' => count(array_filter($rows, fn($row) => 
                        isset($row['dean_approval']) && $row['dean_approval'] === 0
                    )),
                    'today' => count(array_filter($rows, fn($row) => 
                        isset($row['dean_approval_date']) && 
                        date('Y-m-d', strtotime($row['dean_approval_date'])) === date('Y-m-d')
                    ))
                ];
                ?>
                
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-file-alt text-primary me-2"></i>
                            Total Submissions
                        </span>
                        <span class="badge bg-primary rounded-pill"><?php echo $stats['total']; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-clock text-warning me-2"></i>
                            Pending Review
                        </span>
                        <span class="badge bg-warning rounded-pill"><?php echo $stats['pending']; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Approved
                        </span>
                        <span class="badge bg-success rounded-pill"><?php echo $stats['approved']; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-undo text-danger me-2"></i>
                            Returned
                        </span>
                        <span class="badge bg-danger rounded-pill"><?php echo $stats['returned']; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <i class="fas fa-calendar-check text-info me-2"></i>
                            Processed Today
                        </span>
                        <span class="badge bg-info rounded-pill"><?php echo $stats['today']; ?></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>