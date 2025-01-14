<!-- Add User Modal Component -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="add_user.php" method="post" class="needs-validation" novalidate>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-user-plus text-primary me-2"></i>
                        Add New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Name Field -->
                    <div class="mb-4">
                        <label for="addUserName" class="form-label fw-medium">Full Name</label>
                        <input type="text" class="form-control form-control-lg shadow-sm" 
                               id="addUserName" name="name" required>
                        <div class="invalid-feedback">Please enter a name.</div>
                    </div>

                    <!-- Email Field -->
                    <div class="mb-4">
                        <label for="addUserEmail" class="form-label fw-medium">Email Address</label>
                        <input type="email" class="form-control form-control-lg shadow-sm" 
                               id="addUserEmail" name="email" required>
                        <div class="invalid-feedback">Please enter a valid email.</div>
                    </div>

                    <!-- User Type Field -->
                    <div class="mb-4">
                        <label for="addUserType" class="form-label fw-medium">User Type</label>
                        <select class="form-select form-select-lg shadow-sm" id="addUserType" name="user_type" required>
                            <option value="">Select user type</option>
                            <option value="admin">System Admin</option>
                            <option value="user">Staff</option>
                            <option value="hod">Head of Department</option>
                            <option value="dean">Dean</option>
                        </select>
                        <div class="invalid-feedback">Please select a user type.</div>
                    </div>

                    <!-- Department Field -->
                    <div class="mb-4" id="departmentSection" style="display: none;">
                        <label for="department" class="form-label fw-medium">Department</label>
                        <select class="form-select form-select-lg shadow-sm" id="department" name="department">
                            <option value="">Select department</option>
                            <option value="Software Engineering">Software Engineering</option>
                            <option value="Information Systems">Information Systems</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Cybersecurity">Cybersecurity</option>
                        </select>
                        <small class="text-muted">Required for Staff and HOD</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addUserType = document.getElementById('addUserType');
    const departmentSection = document.getElementById('departmentSection');
    const departmentSelect = document.getElementById('department');

    if (addUserType && departmentSection && departmentSelect) {
        addUserType.addEventListener('change', function() {
            const selectedType = this.value;
            if (selectedType === 'user' || selectedType === 'hod') {
                departmentSection.style.display = 'block';
                departmentSelect.required = true;
            } else {
                departmentSection.style.display = 'none';
                departmentSelect.required = false;
                departmentSelect.value = '';
            }
        });
    }
});
</script>