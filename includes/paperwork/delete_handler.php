<?php
require_once 'includes/audit_logger.php'; // Ensure audit logging is available

function handlePaperworkDeletion($conn, $ppw_id, $user_type, $user_email, $permManager) {
    try {
        error_log("Starting deletion process for paperwork ID: $ppw_id by user: $user_email ($user_type)");
        
        // 1. Check delete permission
        $permManager->requirePermission('delete_submission');

        // 2. Fetch paperwork details with improved query
        $checkStmt = $conn->prepare(
            "SELECT p.*, u.email as owner_email, u.department, u.user_type 
             FROM tbl_ppw p 
             JOIN tbl_users u ON p.id = u.id 
             WHERE p.ppw_id = ?"
        );
        $checkStmt->execute([$ppw_id]);
        $paperwork = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$paperwork) {
            throw new Exception("Paperwork not found.");
        }

        error_log("Paperwork details: " . json_encode([
            'ppw_id' => $ppw_id,
            'owner_email' => $paperwork['owner_email'],
            'user_email' => $user_email,
            'current_stage' => $paperwork['current_stage'],
            'status' => $paperwork['status']
        ]));

        // 3. Enhanced deletion rights verification
        if ($user_type !== 'admin') {
            // a. Check ownership
            if ($paperwork['owner_email'] !== $user_email) {
                error_log("Unauthorized deletion attempt - User: $user_email trying to delete paperwork owned by: {$paperwork['owner_email']}");
                throw new Exception("Unauthorized to delete this paperwork.");
            }

            // b. Check paperwork status
            if (!in_array($paperwork['current_stage'], ['draft', 'submitted', 'returned'])) {
                error_log("Cannot delete paperwork in stage: {$paperwork['current_stage']}");
                throw new Exception("Cannot delete paperwork that is under review.");
            }

            // c. Additional check for approvals
            if ($paperwork['status'] == '1') {
                error_log("Cannot delete approved paperwork");
                throw new Exception("Cannot delete approved paperwork.");
            }
        }

        // 4. Delete file with improved error handling
        if (!empty($paperwork['document_path'])) {
            $filepath = 'uploads/' . $paperwork['document_path'];
            if (file_exists($filepath)) {
                if (!unlink($filepath)) {
                    error_log("Failed to delete file: $filepath");
                    throw new Exception("Failed to delete the attached file.");
                }
                error_log("Successfully deleted file: $filepath");
            }
        }

        // 5. Delete from database with transaction
        $conn->beginTransaction();
        try {
            $deleteStmt = $conn->prepare("DELETE FROM tbl_ppw WHERE ppw_id = ?");
            if (!$deleteStmt->execute([$ppw_id])) {
                throw new Exception("Database deletion failed");
            }
            $conn->commit();
            error_log("Successfully deleted paperwork from database");
            
            // 6. Log the deletion
            logAudit(
                $conn,
                'DELETE_PAPERWORK',
                "Paperwork deleted\n" .
                "ID: $ppw_id\n" .
                "Ref: {$paperwork['ref_number']}\n" .
                "Stage: {$paperwork['current_stage']}\n" .
                "Deleted by: $user_email ($user_type)\n" .
                "Owner: {$paperwork['owner_email']}"
            );

            return true;

        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Delete paperwork error: " . $e->getMessage());
        throw $e;
    }
}
?>