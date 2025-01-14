<?php
// Only show approval actions if user has permission
if (!$permManager->hasPermission('approve_submissions')) {
    return;
}

// Get current stage and validation
$current_stage = $paperwork['current_stage'];
$can_approve = match($user_type) {
    'hod' => ($current_stage === 'submitted' || $current_stage === 'hod_review') 
             && empty($paperwork['dean_approval_date']),
    'dean' => $current_stage === 'dean_review' 
             && $paperwork['hod_approval'] === 1 
             && empty($paperwork['dean_approval_date'])
             && !isset($paperwork['dean_approval']),  // Added check
    default => false
};

// Debug logging
error_log("Approval Check: " . json_encode([
    'user_type' => $user_type,
    'current_stage' => $current_stage,
    'can_approve' => $can_approve,
    'hod_approval' => $paperwork['hod_approval'],
    'dean_approval_date' => $paperwork['dean_approval_date'] ?? 'null'
]));

if (!$can_approve) {
    return;
}
?>

<!-- Approval Actions Section -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h5 class="card-title mb-4">
            <i class="fas fa-tasks text-primary me-2"></i>
            Approval Actions
        </h5>
        <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal">
                <i class="fas fa-check me-2"></i>Approve
            </button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#disapproveModal">
                <i class="fas fa-times me-2"></i>Return for Modification
            </button>
        </div>
    </div>
</div>
