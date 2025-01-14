<!-- Welcome Section -->
<div class="container py-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-wave-square text-primary" style="font-size: 2rem;"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h2 class="card-title h4 mb-1">
                        Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!
                    </h2>
                    <p class="card-text text-muted mb-0">
                        <?php if ($_SESSION['user_type'] === 'admin'): ?>
                            Manage system users and monitor paperwork submissions through your admin dashboard.
                        <?php elseif ($_SESSION['user_type'] === 'hod'): ?>
                            Review and approve department paperwork submissions efficiently.
                        <?php elseif ($_SESSION['user_type'] === 'dean'): ?>
                            Review and provide final approval for paperwork submissions.
                        <?php else: ?>
                            Track and manage your paperwork submissions in one place.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="d-flex justify-content-end">
                <a href="create_paperwork.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>New Paperwork
                </a>
            </div>
        </div>
    </div>
</div>