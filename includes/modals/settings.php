<?php
// Get permission manager from Auth if not already available
if (!isset($permManager)) {
    $auth = Auth::getInstance();
    $permManager = $auth->getPermManager();
}

// Get user type and permissions
$user_type = $_SESSION['user_type'];
$can_manage_settings = $permManager->hasPermission('manage_settings');

// Get user settings from database
$conn = $auth->getConnection();
$stmt = $conn->prepare("SELECT settings FROM tbl_users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$result = $stmt->fetch();

// Parse JSON settings or use defaults
$settings = [];
if ($result && $result['settings']) {
    $settings = json_decode($result['settings'], true);
}

// Set defaults if not exists
$settings = array_merge([
    'theme' => 'light',
    'compact_view' => false,
    'email_notifications' => true,
    'browser_notifications' => false
], $settings);

// Store in session for easy access
$_SESSION['settings'] = $settings;
?>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="settingsForm" class="needs-validation" novalidate>
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-cog text-primary me-2"></i>
                        Settings
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <!-- Appearance -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-palette text-primary me-2"></i>
                            Appearance
                        </h6>
                        <div class="bg-light rounded p-3">
                            <div class="mb-3">
                                <label class="form-label">Theme</label>
                                <select class="form-select" name="theme" id="themeSelect">
                                    <option value="light" <?php echo ($settings['theme'] === 'light') ? 'selected' : ''; ?>>Light</option>
                                    <option value="dark" <?php echo ($settings['theme'] === 'dark') ? 'selected' : ''; ?>>Dark</option>
                                    <option value="system" <?php echo ($settings['theme'] === 'system') ? 'selected' : ''; ?>>Use System Theme</option>
                                </select>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="compactView" name="compact_view" 
                                    <?php echo ($settings['compact_view']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="compactView">Compact View</label>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-bell text-primary me-2"></i>
                            Notifications
                        </h6>
                        <div class="bg-light rounded p-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="emailNotifications" name="email_notifications" 
                                    <?php echo ($settings['email_notifications']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="emailNotifications">
                                    Email Notifications
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="browserNotifications" name="browser_notifications" 
                                    <?php echo ($settings['browser_notifications']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="browserNotifications">
                                    Browser Notifications
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>