<!-- Success/Return Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <!-- Dynamic header class based on action -->
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-check-circle me-2"></i>
                    <span id="successTitle">Status Update</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <div class="status-animation mb-4">
                    <!-- Icon will be dynamically set -->
                    <i id="statusIcon" style="font-size: 4rem;"></i>
                </div>
                <h5 class="mb-3" id="successMessage"></h5>
                <div class="text-muted mb-3" id="successDetails"></div>
                <!-- Note section -->
                <div id="noteSection" class="d-none">
                    <div class="alert alert-secondary text-start p-3 mb-3">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-comment-alt me-2"></i>Note:
                        </h6>
                        <p class="mb-0" id="noteText"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal" onclick="window.location.href='dashboard.php'">
                    Return to Dashboard
                </button>
            </div>
        </div>
    </div>
</div>