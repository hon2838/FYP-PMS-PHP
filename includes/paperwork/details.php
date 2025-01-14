<?php
require_once __DIR__ . '/../utilities.php';
?>

<!-- Paperwork Details Section -->
<div class="mb-4">
    <!-- Basic Details -->
    <div class="row mb-4">
        <label class="col-sm-3 col-form-label fw-medium">Reference Number:</label>
        <div class="col-sm-9">
            <input type="text" 
                   class="form-control form-control-lg shadow-sm" 
                   value="<?php echo htmlspecialchars($paperwork['ref_number']); ?>" 
                   readonly>
        </div>
    </div>

    <div class="row mb-4">
        <label class="col-sm-3 col-form-label fw-medium">Paperwork Name:</label>
        <div class="col-sm-9">
            <input type="text" 
                   class="form-control form-control-lg shadow-sm" 
                   value="<?php echo htmlspecialchars($paperwork['project_name']); ?>" 
                   readonly>
        </div>
    </div>

    <div class="row mb-4">
        <label class="col-sm-3 col-form-label fw-medium">Type:</label>
        <div class="col-sm-9">
            <input type="text" 
                   class="form-control form-control-lg shadow-sm" 
                   value="<?php echo htmlspecialchars($paperwork['ppw_type']); ?>" 
                   readonly>
        </div>
    </div>

    <div class="row mb-4">
        <label class="col-sm-3 col-form-label fw-medium">Session:</label>
        <div class="col-sm-9">
            <input type="text" 
                   class="form-control form-control-lg shadow-sm" 
                   value="<?php echo htmlspecialchars($paperwork['session']); ?>" 
                   readonly>
        </div>
    </div>

    <div class="row mb-4">
        <label class="col-sm-3 col-form-label fw-medium">Submitted By:</label>
        <div class="col-sm-9">
            <input type="text" 
                   class="form-control form-control-lg shadow-sm" 
                   value="<?php echo htmlspecialchars($paperwork['submitted_by']); ?>" 
                   readonly>
        </div>
    </div>

    <?php if ($user_type === 'dean' || $user_type === 'admin'): ?>
    <div class="row mb-4">
        <label class="col-sm-3 col-form-label fw-medium">Department:</label>
        <div class="col-sm-9">
            <input type="text" 
                   class="form-control form-control-lg shadow-sm" 
                   value="<?php echo htmlspecialchars($paperwork['department']); ?>" 
                   readonly>
        </div>
    </div>
    <?php endif; ?>

    <div class="row mb-4">
        <label class="col-sm-3 col-form-label fw-medium">Submission Date:</label>
        <div class="col-sm-9">
            <input type="text" 
                   class="form-control form-control-lg shadow-sm" 
                   value="<?php echo date('d M Y, h:i A', strtotime($paperwork['submission_time'])); ?>" 
                   readonly>
        </div>
    </div>

    <!-- Additional Details for HOD/Dean/Admin -->
    <?php if ($user_type !== 'user'): ?>
        <?php if ($paperwork['hod_approval_date']): ?>
        <div class="row mb-4">
            <label class="col-sm-3 col-form-label fw-medium">HOD Review:</label>
            <div class="col-sm-9">
                <div class="mb-2">
                    <span class="badge <?php echo $paperwork['hod_approval'] ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo $paperwork['hod_approval'] ? 'Approved' : 'Returned'; ?>
                    </span>
                    <small class="text-muted ms-2">
                        <?php echo date('d M Y, h:i A', strtotime($paperwork['hod_approval_date'])); ?>
                    </small>
                </div>
                <?php if ($paperwork['hod_note']): ?>
                <p class="text-muted mb-0 small"><?php echo htmlspecialchars($paperwork['hod_note']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($paperwork['dean_approval_date']): ?>
        <div class="row mb-4">
            <label class="col-sm-3 col-form-label fw-medium">Dean Review:</label>
            <div class="col-sm-9">
                <div class="mb-2">
                    <span class="badge <?php echo $paperwork['dean_approval'] ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo $paperwork['dean_approval'] ? 'Approved' : 'Returned'; ?>
                    </span>
                    <small class="text-muted ms-2">
                        <?php echo date('d M Y, h:i A', strtotime($paperwork['dean_approval_date'])); ?>
                    </small>
                </div>
                <?php if ($paperwork['dean_note']): ?>
                <p class="text-muted mb-0 small"><?php echo htmlspecialchars($paperwork['dean_note']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>