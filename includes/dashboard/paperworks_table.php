<?php
require_once __DIR__ . '/../utilities.php';
// Get current page number for pagination
$results_per_page = 10;
$pageno = $_GET['pageno'] ?? 1;

// Filter rows first if user is staff
if ($_SESSION['user_type'] === 'staff') {
    $rows = array_filter($rows, function($row) {
        return $row['user_email'] === $_SESSION['email'];
    });
}

$total_results = count($rows);
$number_of_pages = ceil($total_results / $results_per_page);
$page_first_result = ($pageno - 1) * $results_per_page;

// Get slice of rows for current page
$current_page_rows = array_slice($rows, $page_first_result, $results_per_page);
?>

<!-- Paperworks Table Section -->
<div class="container mb-4">
    <div class="card border-0 shadow-sm">
        <!-- Card Header -->
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <div class="icon-circle bg-primary-soft me-3">
                        <i class="fas fa-clipboard-list text-primary"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">
                            <?php echo $_SESSION['user_type'] === 'admin' ? 'All Paperworks' : 'Your Paperworks'; ?>
                        </h5>
                        <small class="text-muted">Manage your paperwork submissions</small>
                    </div>
                </div>
                <?php if ($permManager->hasPermission('create_submission')): ?>
                <a href="create_paperwork.php" class="btn btn-primary btn-sm px-3 d-flex align-items-center">
                    <i class="fas fa-plus me-2"></i>New Paperwork
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table Section -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th scope="col" class="px-4 py-3 text-uppercase fs-12">Reference</th>
                            <th scope="col" class="px-4 py-3 text-uppercase fs-12">Name</th>
                            <th scope="col" class="px-4 py-3 text-uppercase fs-12">Staff ID</th>
                            <th scope="col" class="px-4 py-3 text-uppercase fs-12">Session</th>
                            <?php if ($_SESSION['user_type'] === 'dean' || $_SESSION['user_type'] === 'admin'): ?>
                            <th scope="col" class="px-4 py-3 text-uppercase fs-12">Department</th>
                            <?php endif; ?>
                            <th scope="col" class="px-4 py-3 text-uppercase fs-12">Actions</th>
                            <th scope="col" class="px-4 py-3 text-uppercase fs-12">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_page_rows as $row): ?>
                        <tr class="align-middle hover-shadow">
                            <td class="px-4 py-3">
                                <span class="fw-medium"><?php echo htmlspecialchars($row['ref_number']); ?></span>
                            </td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="px-4 py-3"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td class="px-4 py-3">
                                <span class="badge bg-light text-dark">
                                    <?php echo htmlspecialchars($row['session']); ?>
                                </span>
                            </td>
                            <?php if ($_SESSION['user_type'] === 'dean' || $_SESSION['user_type'] === 'admin'): ?>
                            <td class="px-4 py-3">
                                <span class="badge bg-light text-dark">
                                    <?php echo htmlspecialchars($row['department'] ?? ''); ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td class="px-4 py-3">
                                <div class="btn-group" role="group">
                                    <!-- Action Buttons -->
                                    <?php if ($permManager->hasPermission('view_submissions')): ?>
                                        <a href="viewpaperwork.php?ppw_id=<?php echo htmlspecialchars($row['ppw_id']); ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($permManager->hasPermission('edit_submission')): ?>
                                        <a href="editpaperwork.php?ppw_id=<?php echo htmlspecialchars($row['ppw_id']); ?>" 
                                           class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($permManager->hasPermission('delete_submission')): ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="confirmDelete(<?php echo htmlspecialchars($row['ppw_id']); ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <button type="button" 
                                        class="btn btn-sm status-badge status-<?php echo $row['current_stage']; ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#statusModal_<?php echo htmlspecialchars($row['ppw_id']); ?>">
                                    <i class="fas fa-circle me-1"></i>
                                    <?php echo getStatusText($row['current_stage']); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Status Modals -->
<?php foreach ($rows as $row): ?>
<div class="modal fade" id="statusModal_<?php echo htmlspecialchars($row['ppw_id']); ?>" 
     tabindex="-1" 
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    Status Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <!-- Timeline -->
                <div class="timeline">
                    <!-- Submission -->
                    <div class="timeline-item">
                        <div class="timeline-badge <?php 
                            if ($row['current_stage'] === 'submitted') {
                                echo 'bg-info';
                            } elseif ($row['submission_time']) {
                                echo 'bg-success';
                            } else {
                                echo 'bg-secondary';
                            }
                        ?>">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="mb-2">Submitted</h6>
                            <?php if ($row['submission_time']): ?>
                                <p class="mb-0 text-muted">
                                    <?php echo date('d M Y, h:i A', strtotime($row['submission_time'])); ?>
                                </p>
                            <?php else: ?>
                                <p class="mb-0 text-muted">Pending submission</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- HOD Review -->
                    <div class="timeline-item">
                        <div class="timeline-badge <?php 
                            if ($row['current_stage'] === 'hod_review') {
                                echo 'bg-info';
                            } elseif ($row['hod_approval_date']) {
                                echo $row['hod_approval'] ? 'bg-success' : 'bg-danger';
                            } else {
                                echo 'bg-secondary';
                            }
                        ?>">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="mb-2">HOD Review</h6>
                            <?php if ($row['hod_approval_date']): ?>
                                <p class="mb-2"><?php echo $row['hod_approval'] ? 'Approved' : 'Returned'; ?></p>
                                <?php if ($row['hod_note']): ?>
                                    <p class="mb-2 text-muted"><?php echo htmlspecialchars($row['hod_note']); ?></p>
                                <?php endif; ?>
                                <small class="text-muted">
                                    <?php echo date('d M Y, h:i A', strtotime($row['hod_approval_date'])); ?>
                                </small>
                            <?php else: ?>
                                <p class="mb-0 text-muted"><?php echo $row['current_stage'] === 'hod_review' ? 'Under review' : 'Pending HOD review'; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Dean Review -->
                    <div class="timeline-item">
                        <div class="timeline-badge <?php 
                            if ($row['current_stage'] === 'dean_review') {
                                echo 'bg-info';
                            } elseif ($row['dean_approval_date']) {
                                echo $row['dean_approval'] ? 'bg-success' : 'bg-danger';
                            } else {
                                echo 'bg-secondary';
                            }
                        ?>">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="mb-2">Dean Review</h6>
                            <?php if ($row['dean_approval_date']): ?>
                                <p class="mb-2"><?php echo $row['dean_approval'] ? 'Approved' : 'Returned'; ?></p>
                                <?php if ($row['dean_note']): ?>
                                    <p class="mb-2 text-muted"><?php echo htmlspecialchars($row['dean_note']); ?></p>
                                <?php endif; ?>
                                <small class="text-muted">
                                    <?php echo date('d M Y, h:i A', strtotime($row['dean_approval_date'])); ?>
                                </small>
                            <?php else: ?>
                                <p class="mb-0 text-muted"><?php echo $row['current_stage'] === 'dean_review' ? 'Under review' : 'Pending Dean review'; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Final Status -->
                    <div class="timeline-item">
                        <div class="timeline-badge <?php 
                            if ($row['current_stage'] === 'approved') {
                                echo 'bg-success';
                            } elseif ($row['current_stage'] === 'rejected') {
                                echo 'bg-danger';
                            } else {
                                echo 'bg-secondary';
                            }
                        ?>">
                            <i class="fas fa-flag-checkered"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="mb-2">Final Status</h6>
                            <p class="mb-0 <?php echo $row['current_stage'] === 'approved' || $row['current_stage'] === 'rejected' ? 'text-muted' : 'text-secondary'; ?>">
                                <?php 
                                if ($row['current_stage'] === 'approved') {
                                    echo 'Paperwork approved';
                                } elseif ($row['current_stage'] === 'rejected') {
                                    echo 'Returned for modifications';
                                } else {
                                    echo 'Pending completion';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
function confirmDelete(ppw_id) {
    NotificationModal.show({
        type: 'warning',
        title: 'Confirm Deletion',
        message: 'Are you sure you want to delete this paperwork?',
        details: 'This action cannot be undone.',
        buttonText: 'Delete',
        buttonClass: 'btn-danger',
        iconClass: 'fa-trash-alt',
        showCancelButton: true,
        onConfirm: () => {
            window.location.href = `dashboard.php?submit=delete&ppw_id=${ppw_id}`;
        }
    });
    return false;
}
</script>