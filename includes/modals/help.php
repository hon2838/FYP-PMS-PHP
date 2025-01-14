<?php 
// Get user type from session
$user_type = $_SESSION['user_type'] ?? '';
?>
<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-question-circle text-primary me-2"></i>
                    Help & Documentation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <!-- Role-specific help content -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-user-circle text-primary me-2"></i>
                                    Your Role: <?php echo ucfirst($user_type); ?>
                                </h6>
                                <ul class="list-unstyled">
                                    <?php if ($user_type === 'admin'): ?>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Manage system users</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>View all submissions</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>System configuration</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Generate reports</li>
                                    <?php elseif ($user_type === 'hod'): ?>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Review department papers</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Department statistics</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Submit paperwork</li>
                                    <?php elseif ($user_type === 'dean'): ?>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Final paper review</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>View audit logs</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Approval statistics</li>
                                    <?php else: ?>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Submit paperwork</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Track submissions</li>
                                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>View history</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-key text-primary me-2"></i>
                                    Available Permissions
                                </h6>
                                <ul class="list-unstyled">
                                    <?php 
                                    $permissions = $permManager->getPermissions();
                                    foreach ($permissions as $permission): 
                                        $icon = match($permission) {
                                            'create_submission' => 'plus',
                                            'edit_submission' => 'edit',
                                            'delete_submission' => 'trash',
                                            'view_submissions' => 'eye',
                                            'approve_submissions' => 'check-double',
                                            'manage_users' => 'users-cog',
                                            'generate_reports' => 'chart-bar',
                                            default => 'check'
                                        };
                                    ?>
                                    <li class="mb-2">
                                        <i class="fas fa-<?php echo $icon; ?> text-success me-2"></i>
                                        <?php echo ucwords(str_replace('_', ' ', $permission)); ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Got it!</button>
            </div>
        </div>
    </div>
</div>