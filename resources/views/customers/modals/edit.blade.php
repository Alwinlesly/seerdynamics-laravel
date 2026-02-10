<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCustomerForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="editCustomerId" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editLastName" name="last_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="editEmail" name="email" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <small class="text-muted">(leave blank to keep current)</small></label>
                            <input type="password" class="form-control" id="editPassword" name="password" minlength="6">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-control" id="editCompany" name="company">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" id="editPhone" name="phone">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="editContactPerson" name="contact_person_desg">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Country</label>
                            <input type="text" class="form-control" id="editCountry" name="country">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" id="editAddress" name="address" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="active" id="editActive" value="1">
                            <label class="form-check-label" for="editActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Submit edit customer form
    $('#editCustomerForm').on('submit', function(e) {
        e.preventDefault();
        
        const customerId = $('#editCustomerId').val();
        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Updating...');
        
        $.ajax({
            url: `/customers/${customerId}`,
            method: 'PUT',
            data: formData,
            success: function(response) {
                if (!response.error) {
                    $('#editCustomerModal').modal('hide');
                    loadCustomers();
                    
                    // Show success message
                    const alert = $('<div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">' +
                        '<i class="bi bi-check-circle me-2"></i>' + response.message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
                    $('body').append(alert);
                    setTimeout(() => alert.remove(), 3000);
                } else {
                    alert(response.message || 'Failed to update customer');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to update customer';
                alert(message);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('Update Customer');
            }
        });
    });
});
</script>
