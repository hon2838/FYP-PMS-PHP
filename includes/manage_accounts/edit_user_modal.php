<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="edit_user.php" method="post" class="needs-validation" novalidate>
                <input type="hidden" name="id" id="editUserId">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">
                        <i class="fas fa-user-edit text-primary me-2"></i>
                        Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <!-- Name Field -->
                    <div class="mb-4">
                        <label for="editUserName" class="form-label fw-medium">Full Name</label>
                        <input type="text" class="form-control form-control-lg shadow-sm" 
                               id="editUserName" name="name" required>
                        <div class="invalid-feedback">Please enter a name.</div>
                    </div>

                    <!-- Email Field -->
                    <div class="mb-4">
                        <label for="editUserEmail" class="form-label fw-medium">Email Address</label>
                        <input type="email" class="form-control form-control-lg shadow-sm" 
                               id="editUserEmail" name="email" required>
                        <div class="invalid-feedback">Please enter a valid email.</div>
                    </div>

                    <!-- Reset Password Section -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-medium mb-0">Password Options</label>
                            <button type="button" 
                                    class="btn btn-warning btn-sm" 
                                    onclick="resetUserPassword()">
                                <i class="fas fa-key me-1"></i>Reset Password
                            </button>
                        </div>
                        <div class="alert alert-info d-none" id="passwordResetInfo">
                            <!-- Will be populated when password is reset -->
                        </div>
                    </div>

                    <!-- User Type Field -->
                    <div class="mb-4">
                        <label for="editUserType" class="form-label fw-medium">User Type</label>
                        <select class="form-select form-select-lg shadow-sm" id="editUserType" name="user_type" required>
                            <option value="">Select user type</option>
                            <option value="admin">System Admin</option>
                            <option value="staff">Staff</option>
                            <option value="hod">Head of Department</option>
                            <option value="dean">Dean</option>
                        </select>
                        <div class="invalid-feedback">Please select a user type.</div>
                    </div>

                    <!-- Department Field -->
                    <div class="mb-4" id="editDepartmentSection" style="display: none;">
                        <label for="editDepartment" class="form-label fw-medium">Department</label>
                        <select class="form-select form-select-lg shadow-sm" id="editDepartment" name="department">
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
                    <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get edit buttons
    const editButtons = document.querySelectorAll('.editUserBtn');
    
    // Add click handlers
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Get user data from button attributes
            const id = this.dataset.id;
            const name = this.dataset.name;
            const email = this.dataset.email;
            const userType = this.dataset.user_type;
            
            // Set form values
            document.getElementById('editUserId').value = id;
            document.getElementById('editUserName').value = name;
            document.getElementById('editUserEmail').value = email;
            document.getElementById('editUserType').value = userType;
            
            // Show/hide department section based on user type
            const departmentSection = document.getElementById('editDepartmentSection');
            if (userType === 'staff' || userType === 'hod') {
                departmentSection.style.display = 'block';
                document.getElementById('editDepartment').required = true;
            } else {
                departmentSection.style.display = 'none';
                document.getElementById('editDepartment').required = false;
                document.getElementById('editDepartment').value = '';
            }
        });
    });

    // Handle user type changes
    const editUserType = document.getElementById('editUserType');
    editUserType.addEventListener('change', function() {
        const departmentSection = document.getElementById('editDepartmentSection');
        const departmentSelect = document.getElementById('editDepartment');
        
        if (this.value === 'staff' || this.value === 'hod') {
            departmentSection.style.display = 'block';
            departmentSelect.required = true;
        } else {
            departmentSection.style.display = 'none';
            departmentSelect.required = false;
            departmentSelect.value = '';
        }
    });
});

function resetUserPassword() {
    const userId = document.getElementById('editUserId').value;
    const userEmail = document.getElementById('editUserEmail').value;
    
    if (confirm('Are you sure you want to reset the password for this user?')) {
        fetch('reset_user_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                user_id: userId,
                email: userEmail 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const infoDiv = document.getElementById('passwordResetInfo');
                infoDiv.classList.remove('d-none');
                infoDiv.innerHTML = `
                    <p class="mb-1"><strong>New Password:</strong> ${data.password}</p>
                    <p class="mb-0 small">An email has been sent to the user with their new password.</p>
                `;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while resetting the password.');
        });
    }
}
</script>