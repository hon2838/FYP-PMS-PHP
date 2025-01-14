<!-- Users Table Component -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-users text-primary me-2"></i>
                Manage Users
            </h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus me-2"></i>Add User
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-4"><?php echo htmlspecialchars($user['name']); ?></td>
                        <td class="px-4"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="px-4">
                            <span class="badge bg-primary"><?php echo ucfirst(htmlspecialchars($user['user_type'])); ?></span>
                        </td>
                        <td class="px-4"><?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?></td>
                        <td class="px-4">
                            <span class="badge <?php echo $user['active'] ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo $user['active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-4">
                            <div class="btn-group">
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary editUserBtn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal"
                                        data-id="<?php echo $user['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                        data-user_type="<?php echo htmlspecialchars($user['user_type']); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="delete_user.php?id=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>