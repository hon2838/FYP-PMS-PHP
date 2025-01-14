<?php
// Common utility functions

function getStatusText($stage) {
    return match($stage) {
        'submitted' => 'Submitted',
        'hod_review' => 'HOD Review',
        'dean_review' => 'Dean Review',
        'approved' => 'Approved',
        'returned' => 'Returned',  // Make sure this status is included
        'rejected' => 'Rejected',
        'draft' => 'Draft',
        default => 'Processing'
    };
}

function showNotification($type, $title, $message, $details = '', $redirectUrl = null) {
    // Include required files if not already included
    require_once 'includes/modals/notification.php';
    
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure Bootstrap and notifications.js are loaded
            const bootstrapScript = document.createElement('script');
            bootstrapScript.src = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js';
            
            const notificationsScript = document.createElement('script');
            notificationsScript.src = 'js/notifications.js';
            
            bootstrapScript.onload = function() {
                document.body.appendChild(notificationsScript);
                notificationsScript.onload = function() {
                    NotificationModal.show({
                        type: '" . addslashes($type) . "',
                        title: '" . addslashes($title) . "',
                        message: '" . addslashes($message) . "',
                        " . ($details ? "details: '" . addslashes($details) . "'," : "") . "
                        " . ($redirectUrl ? "redirectUrl: '" . addslashes($redirectUrl) . "'" : "") . "
                    });
                };
            };
            
            document.body.appendChild(bootstrapScript);
        });
    </script>";
}