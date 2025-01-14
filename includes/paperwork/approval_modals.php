<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="viewpaperwork.php?ppw_id=<?php echo htmlspecialchars($paperwork['ppw_id']); ?>" method="post">
                <input type="hidden" name="ppw_id" value="<?php echo htmlspecialchars($paperwork['ppw_id']); ?>">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Approve Paperwork
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="mb-4">
                        <label for="approveNote" class="form-label fw-medium">Approval Note:</label>
                        <textarea 
                            class="form-control form-control-lg shadow-sm" 
                            id="approveNote" 
                            name="note" 
                            rows="3"
                            placeholder="Add any notes or comments (optional)"
                        ></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="action" value="approve" class="btn btn-success px-4">
                        <i class="fas fa-check me-2"></i>Confirm Approval
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return for Modification Modal -->
<div class="modal fade" id="disapproveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="viewpaperwork.php?ppw_id=<?php echo htmlspecialchars($paperwork['ppw_id']); ?>" method="post">
                <input type="hidden" name="ppw_id" value="<?php echo htmlspecialchars($paperwork['ppw_id']); ?>">
                <div class="modal-header border-0 bg-warning">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-undo text-dark me-2"></i>
                        Return for Modification
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will return the paperwork to the user for modification.
                    </div>
                    <div class="mb-4">
                        <label for="disapproveNote" class="form-label fw-medium">Feedback Note: <span class="text-danger">*</span></label>
                        <textarea 
                            class="form-control form-control-lg shadow-sm" 
                            id="disapproveNote" 
                            name="note" 
                            rows="3"
                            placeholder="Provide specific feedback on what needs to be modified"
                            required
                        ></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="action" value="disapprove" class="btn btn-warning px-4">
                        <i class="fas fa-undo me-2"></i>Return for Modification
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmAction(action, ppw_id) {
    NotificationModal.show({
        type: 'warning',
        title: 'Confirm ' + (action === 'approve' ? 'Approval' : 'Return'),
        message: 'Are you sure you want to ' + (action === 'approve' ? 'approve' : 'return') + ' this paperwork?',
        details: 'This action cannot be undone.',
        buttonText: 'Confirm',
        redirectUrl: `viewpaperwork.php?ppw_id=${ppw_id}&action=${action}`
    });
    return false;
}
</script>