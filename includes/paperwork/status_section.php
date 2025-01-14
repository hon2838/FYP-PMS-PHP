<?php
// Helper functions for stage status checks
function isStageComplete($stage, $paperwork) {
    switch($stage) {
        case 'submission':
            return !empty($paperwork['submission_time']);
        case 'hod_review':
            return !empty($paperwork['hod_approval_date']) && 
                   $paperwork['hod_approval'] === 1;
        case 'dean_review':
            return !empty($paperwork['dean_approval_date']) && 
                   $paperwork['dean_approval'] === 1;
        default:
            return false;
    }
}

function isStageRejected($stage, $paperwork) {
    switch($stage) {
        case 'hod_review':
            return !empty($paperwork['hod_approval_date']) && 
                   ($paperwork['hod_approval'] === 0 || 
                    $paperwork['current_stage'] === 'returned');
        case 'dean_review':
            return !empty($paperwork['dean_approval_date']) && 
                   ($paperwork['dean_approval'] === 0 || 
                    $paperwork['current_stage'] === 'returned');
        default:
            return false;
    }
}
?>

<!-- Status Timeline Section -->
<div class="row mb-4">
    <div class="col-12">
        <h5 class="mb-4">
            <i class="fas fa-history text-primary me-2"></i>
            Status Timeline
        </h5>
        
        <div class="timeline">
            <!-- Submission -->
            <div class="timeline-item <?php echo $current_stage === 'submitted' ? 'current' : (isStageComplete('submission', $paperwork) ? 'completed' : 'pending'); ?>">
                <div class="timeline-badge">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div class="timeline-content">
                    <h6 class="mb-2">Submitted</h6>
                    <p class="mb-0 text-muted small">
                        <?php echo date('d M Y, h:i A', strtotime($paperwork['submission_time'])); ?>
                    </p>
                </div>
            </div>

            <!-- HOD Review -->
            <div class="timeline-item <?php 
                if ($current_stage === 'hod_review') {
                    echo 'current';
                } elseif (isStageComplete('hod_review', $paperwork)) {
                    echo 'completed';
                } elseif (isStageRejected('hod_review', $paperwork)) {
                    echo 'rejected';
                } else {
                    echo 'pending';
                }
            ?>">
                <div class="timeline-badge">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="timeline-content">
                    <h6 class="mb-2">HOD Review</h6>
                    <?php if ($paperwork['hod_approval_date']): ?>
                        <p class="mb-2"><?php echo $paperwork['hod_approval'] ? 'Approved' : 'Returned'; ?></p>
                        <p class="mb-0 text-muted small">
                            <?php echo date('d M Y, h:i A', strtotime($paperwork['hod_approval_date'])); ?>
                        </p>
                    <?php else: ?>
                        <p class="mb-0 text-muted">
                            <?php echo $current_stage === 'hod_review' ? 'Under review' : 'Pending'; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Dean Review -->
            <div class="timeline-item <?php 
                if ($current_stage === 'dean_review') {
                    echo 'current';
                } elseif (isStageComplete('dean_review', $paperwork)) {
                    echo 'completed';
                } elseif (isStageRejected('dean_review', $paperwork)) {
                    echo 'rejected';
                } else {
                    echo 'pending';
                }
            ?>">
                <div class="timeline-badge">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="timeline-content">
                    <h6 class="mb-2">Dean Review</h6>
                    <?php if ($paperwork['dean_approval_date']): ?>
                        <p class="mb-2"><?php echo $paperwork['dean_approval'] ? 'Approved' : 'Returned'; ?></p>
                        <p class="mb-0 text-muted small">
                            <?php echo date('d M Y, h:i A', strtotime($paperwork['dean_approval_date'])); ?>
                        </p>
                    <?php else: ?>
                        <p class="mb-0 text-muted">
                            <?php echo $current_stage === 'dean_review' ? 'Under review' : 'Pending'; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>