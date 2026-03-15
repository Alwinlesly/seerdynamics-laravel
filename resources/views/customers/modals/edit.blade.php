<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
        <h5 class="modal-title" id="editCustomerModalLabel">Edit customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body create-ticket-body pb-4">
        <form id="editCustomerForm">
            @csrf
            @method('PUT')
            <input type="hidden" id="editCustomerId" name="id">
            
            <!-- Row 1: Customer Name / Customer ID -->
            <div class="row mb-3">
                <div class="col-lg-6">
                    <div>
                        <label for="editCompany" class="form-label">Customer name <span class="req">*</span></label>
                        <input type="text" class="form-control" id="editCompany" name="company" required>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div>
                        <label for="editCustomerCode" class="form-label">Customer id <span class="req">*</span></label>
                        <input type="text" class="form-control" id="editCustomerCode" name="customer_code" required>
                    </div>
                </div>
            </div>

            <!-- Row 2: Customer Logo / Contact Person (POC) -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="editCustomerLogo" class="form-label">Customer logo</label>
                    <div class="chat-input py-0">
                        <div class="left-ci d-flex align-items-center gap-0">
                            <input type="text" class="form-control" id="editLogoFileName" placeholder="File" readonly>
                            <input type="file" id="editCustomerLogo" name="profile" class="d-none" accept="image/*">
                            <label for="editCustomerLogo" class="upload-btn mb-0" style="cursor: pointer;">
                                <i class="bi bi-upload"></i>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div>
                        <label for="editContactPerson" class="form-label">Contact person (POC) <span class="req">*</span></label>
                        <input type="text" class="form-control" id="editContactPerson" name="first_name" required>
                    </div>
                </div>
            </div>

            <!-- Row 3: Designation of POC / Customer Address -->
            <div class="row mb-3">
                <div class="col-lg-6">
                    <div>
                        <label for="editDesignation" class="form-label">Designation of POC <span class="req">*</span></label>
                        <input type="text" class="form-control" id="editDesignation" name="contact_person_desg" required>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div>
                        <label for="editAddress" class="form-label">Customer Address <span class="req">*</span></label>
                        <input type="text" class="form-control" id="editAddress" name="address" required>
                    </div>
                </div>
            </div>

            <!-- Row 4: Country / Email -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div>
                        <label for="editCountry" class="form-label">Country <span class="req">*</span></label>
                        <select class="form-select" id="editCountry" name="country" required>
                            <option value="" disabled selected hidden>Select country</option>
                            @php
                                $countries = \App\Models\Country::all();
                            @endphp
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div>
                        <label for="editEmail" class="form-label">Email <span class="req">*</span></label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                    </div>
                </div>
            </div>

            <!-- Row 5: Password / Active Status -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div>
                        <label for="editPassword" class="form-label">Password <small class="text-muted">(leave blank to keep current)</small></label>
                        <input type="password" class="form-control" id="editPassword" name="password" minlength="6">
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-center mt-4">
                    <div class="form-check form-switch w-100">
                        <input class="form-check-input" type="checkbox" name="active" id="editActive" value="1">
                        <label class="form-check-label" for="editActive">Active</label>
                    </div>
                </div>
            </div>

            <!-- Hidden fields -->
            <input type="hidden" name="last_name" id="editLastName" value="">
            <input type="hidden" name="phone" id="editPhone" value="">

            <div class="d-flex justify-content-end gap-2 flex-wrap mt-4">
                <button type="submit" class="btn btn-primary" id="updateCustomerBtn">Update</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Show selected file name for edit logo
    $(document).on('change', '#editCustomerLogo', function() {
        const fileName = this.files && this.files[0] ? this.files[0].name : '';
        $('#editLogoFileName').val(fileName);
    });

    // Submit edit customer form
    $('#editCustomerForm').on('submit', function(e) {
        e.preventDefault();
        
        const customerId = $('#editCustomerId').val();
        
        // Use FormData to handle potential file uploads during edit
        const formData = new FormData(this);
        // We need to add _method PUT manually to FormData for Laravel to process it as a PUT request with files
        formData.append('_method', 'PUT');
        
        const submitBtn = $('#updateCustomerBtn');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Updating...');
        
        $.ajax({
            url: `/customers/${customerId}`,
            method: 'POST', // Use POST with _method=PUT to support FormData files in Laravel
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (!response.error) {
                    $('#editCustomerModal').modal('hide');
                    if (typeof window.loadCustomers === 'function') {
                        window.loadCustomers();
                    }
                    
                    // Show success message
                    showToast('success', response.message || 'Customer updated successfully!');
                } else {
                    showToast('error', response.message || 'Failed to update customer');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to update customer';
                showToast('error', message);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('Update');
            }
        });
    });
});
</script>
@endpush
