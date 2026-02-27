<div class="modal fade" id="createCustomerUserModal" tabindex="-1" aria-labelledby="createCustomerUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
        <h5 class="modal-title" id="createCustomerUserModalLabel">Create new customer user</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body create-ticket-body pb-4">
        <form id="createCustomerUserForm">
            @csrf
            
            <!-- Row 1: Project Client (Full Width) -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div>
                        <label for="cuserCustomer" class="form-label">Project Client <span class="req">*</span></label>
                        <select class="form-select" id="cuserCustomer" name="cuser_customer" required>
                            <option value="" disabled selected hidden>Select customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->company ?? $customer->first_name . ' ' . $customer->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 2: Role (Full Width) - Hidden, default to group 4 -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div>
                        <label for="role" class="form-label">Role <span class="req">*</span></label>
                        <select class="form-select" id="role" name="groups" required>
                            <option value="3">Customer</option>
                            <option value="4" selected>Customer User</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 3: First Name / Last Name -->
            <div class="row mb-3">
                <div class="col-lg-6">
                    <div>
                        <label for="firstName" class="form-label">First name <span class="req">*</span></label>
                        <input type="text" class="form-control" id="firstName" name="first_name" required>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div>
                        <label for="lastName" class="form-label">Last name <span class="req">*</span></label>
                        <input type="text" class="form-control" id="lastName" name="last_name" required>
                    </div>
                </div>
            </div>

            <!-- Row 4: Email / Mobile -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div>
                        <label for="email" class="form-label">Email <span class="req">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <small class="text-muted">This email will not be updated later.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div>
                        <label for="mobile" class="form-label">Mobile</label>
                        <input type="text" class="form-control" id="mobile" name="phone">
                    </div>
                </div>
            </div>

            <!-- Row 5: Password / Confirm Password -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div>
                        <label for="password" class="form-label">Password <span class="req">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div>
                        <label for="confirmPassword" class="form-label">Confirm password <span class="req">*</span></label>
                        <input type="password" class="form-control" id="confirmPassword" name="password_confirm" required>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 flex-wrap mt-4">
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    // Handle form submission
    $('#createCustomerUserForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate password match
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();
        
        if (password !== confirmPassword) {
            showToast('warning', 'Password and confirm password do not match!');
            return;
        }
        
        const formData = {
            _token: '{{ csrf_token() }}',
            first_name: $('#firstName').val(),
            last_name: $('#lastName').val(),
            email: $('#email').val(),
            password: password,
            phone: $('#mobile').val(),
            cuser_customer: $('#cuserCustomer').val(),
            active: 1 // Default to active
        };
        
        $.ajax({
            url: '{{ route("customer-users.store") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (!response.error) {
                    $('#createCustomerUserModal').modal('hide');
                    $('#createCustomerUserForm')[0].reset();
                    loadCustomerUsers();
                    showToast('success', 'Customer user created successfully!');
                } else {
                    showToast('error', 'Error creating customer user: ' + response.message);
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Please check all fields.';
                showToast('error', 'Error creating customer user: ' + error);
            }
        });
    });

    // Reset form when modal closes
    $('#createCustomerUserModal').on('hidden.bs.modal', function() {
        $('#createCustomerUserForm')[0].reset();
    });
});
</script>
