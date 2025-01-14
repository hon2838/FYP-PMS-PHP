<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div id="notificationHeader" class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i id="notificationIcon" class="fas me-2"></i>
                    <span id="notificationTitle">Notification</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <div class="notification-animation mb-4">
                    <i id="notificationMainIcon" style="font-size: 4rem;"></i>
                </div>
                <h5 class="mb-3" id="notificationMessage"></h5>
                <div class="text-muted mb-0" id="notificationDetails"></div>
            </div>
            <div class="modal-footer border-0">
                <!-- Buttons will be dynamically inserted here -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('notificationModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function () {
            window.location.href = 'dashboard.php';
        });
    }
});
</script>

<style>
/* Dark mode support */
[data-theme="dark"] #notificationModal .modal-content {
    background-color: var(--bg-primary);
    color: var(--text-primary);
}

[data-theme="dark"] #notificationModal .text-muted {
    color: var(--text-secondary) !important;
}

[data-theme="dark"] #notificationModal .btn-light {
    background-color: var(--bg-secondary);
    color: var(--text-primary);
    border-color: var(--border-color);
}
</style>