<?php
class PermissionManager {
    private $conn;
    private $user_id;
    private $permissions = [];

    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->loadPermissions();
    }

    public function hasPermission($permission) {
        return in_array($permission, $this->permissions);
    }

    private function loadPermissions() {
        // Get permissions from tbl_role_permissions joined with tbl_permissions
        $stmt = $this->conn->prepare("
            SELECT DISTINCT p.permission_name 
            FROM tbl_role_permissions rp
            JOIN tbl_user_roles ur ON rp.role_id = ur.role_id
            JOIN tbl_permissions p ON rp.permission_id = p.permission_id
            WHERE ur.user_id = ?
        ");

        if ($stmt->execute([$this->user_id])) {
            $this->permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("Loaded permissions for user {$this->user_id}: " . implode(", ", $this->permissions));
        } else {
            error_log("Failed to load permissions for user {$this->user_id}");
        }
    }

    public function requirePermission($permission) {
        if (!$this->hasPermission($permission)) {
            error_log("Permission Check Failed: User {$this->user_id} lacks '{$permission}' permission.");
            throw new Exception("Access denied: Insufficient permissions.");
        }
    }

    public function getPermissions() {
        return $this->permissions;
    }
}
?>