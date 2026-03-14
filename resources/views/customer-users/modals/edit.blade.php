<div class="modal fade" id="editCustomerUserModal" tabindex="-1" aria-labelledby="editCustomerUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
                <h5 class="modal-title" id="editCustomerUserModalLabel">Edit customer user</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body create-ticket-body">
                <form id="editCustomerUserForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="editCustomerUserId" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div>
                                <label for="editFirstName" class="form-label">First name <span class="req">*</span></label>
                                <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label for="editLastName" class="form-label">Last name <span class="req">*</span></label>
                                <input type="text" class="form-control" id="editLastName" name="last_name" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div>
                                <label for="editMobile" class="form-label">Mobile</label>
                                <input type="text" class="form-control" id="editMobile" name="phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label for="editPassword" class="form-label">Password <small>(leave blank to keep current)</small></label>
                                <input type="password" class="form-control" id="editPassword" name="password">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div>
                                <label for="editConfirmPassword" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="editConfirmPassword" name="password_confirm">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div>
                                <label for="editRole" class="form-label">Role <span class="req">*</span></label>
                                <select class="form-select" id="editRole" name="groups" required>
                                    <option value="3">Customer</option>
                                    <option value="4">Customer User</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="editActive" name="active">
                                <label class="form-check-label" for="editActive">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3 align-items-center">
                        <div class="col-lg-12 col-pm justify-content-end">
                            <div class="add-mail-right">
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$('#editCustomerUserForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validate password match if password is provided
    const password = $('#editPassword').val();
    const confirmPassword = $('#editConfirmPassword').val();
    
    if (password && password !== confirmPassword) {
        showToast('warning', 'Password and confirm password do not match!');
        return;
    }

    const cuserId = $('#editCustomerUserId').val();
    const formData = {
        _token: '{{ csrf_token() }}',
        _method: 'PUT',
        first_name: $('#editFirstName').val(),
        last_name: $('#editLastName').val(),
        phone: $('#editMobile').val(),
        groups: $('#editRole').val(),
        active: $('#editActive').is(':checked') ? 1 : 0
    };
    
    // Add password only if provided
    if (password) {
        formData.password = password;
    }
    
    $.ajax({
        url: `/users/client/${cuserId}`,
        method: 'PUT',
        data: formData,
        success: function(response) {
            if (!response.error) {
                $('#editCustomerUserModal').modal('hide');
                if (typeof window.loadCustomerUsers === 'function') {
                    window.loadCustomerUsers();
                }
                showToast('success', 'Customer user updated successfully!');
            } else {
                showToast('error', 'Error updating customer user: ' + response.message);
            }
        },
        error: function(xhr) {
            showToast('error', 'Error updating customer user. Please check all fields.');
        }
    });
});
</script>
@endpush
