<div class="modal fade" id="createCustomerModal" tabindex="-1" aria-labelledby="createCustomerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
        <h5 class="modal-title" id="createCustomerModalLabel">Create new customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body create-ticket-body pb-4">
        <form id="createCustomerForm" enctype="multipart/form-data">
            @csrf
            
            <!-- Row 1: Customer Name / Customer ID -->
            <div class="row mb-3">
                <div class="col-lg-6">
                    <div>
                        <label for="customerName" class="form-label">Customer name <span class="req">*</span></label>
                        <input type="text" class="form-control" id="customerName" name="company" required>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div>
                        <label for="customerCode" class="form-label">Customer id <span class="req">*</span></label>
                        <input type="text" class="form-control" id="customerCode" name="customer_code" required>
                    </div>
                </div>
            </div>

            <!-- Row 2: Customer Logo / Contact Person (POC) -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="customerLogo" class="form-label">Customer logo <span class="req">*</span></label>
                    <div class="chat-input py-0">
                        <div class="left-ci d-flex align-items-center gap-0">
                            <input type="text" class="form-control" id="logoFileName" placeholder="File" readonly>
                            <input type="file" id="customerLogo" name="profile" class="d-none" accept="image/*">
                            <label for="customerLogo" class="upload-btn mb-0" style="cursor: pointer;">
                                <i class="bi bi-upload"></i>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div>
                        <label for="contactPerson" class="form-label">Contact person (POC) <span class="req">*</span></label>
                        <input type="text" class="form-control" id="contactPerson" name="first_name" required>
                    </div>
                </div>
            </div>

            <!-- Row 3: Designation of POC / Customer Address -->
            <div class="row mb-3">
                <div class="col-lg-6">
                    <div>
                        <label for="designation" class="form-label">Designation of POC <span class="req">*</span></label>
                        <input type="text" class="form-control" id="designation" name="contact_person_desg" required>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div>
                        <label for="customerAddress" class="form-label">Customer Address <span class="req">*</span></label>
                        <input type="text" class="form-control" id="customerAddress" name="address" required>
                    </div>
                </div>
            </div>

            <!-- Row 4: Country / Email -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div>
                        <label for="country" class="form-label">Country <span class="req">*</span></label>
                        <select class="form-select" id="country" name="country" required>
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
                        <label for="email" class="form-label">Email <span class="req">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <small class="text-muted">This email will not be updated later.</small>
                    </div>
                </div>
            </div>

            <!-- Hidden fields -->
            <input type="hidden" name="last_name" value="">
            <input type="hidden" name="phone" value="">
            <input type="hidden" name="password" value="thisisdefcust">

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
    // Show selected file name
    $('#customerLogo').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $('#logoFileName').val(fileName);
    });

    // Handle form submission
    $('#createCustomerForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("customers.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (!response.error) {
                    $('#createCustomerModal').modal('hide');
                    $('#createCustomerForm')[0].reset();
                    $('#logoFileName').val('');
                    loadCustomers();
                    alert('Customer created successfully!');
                } else {
                    alert('Error creating customer: ' + response.message);
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'Please check all fields.';
                alert('Error creating customer: ' + error);
            }
        });
    });

    // Reset form when modal closes
    $('#createCustomerModal').on('hidden.bs.modal', function() {
        $('#createCustomerForm')[0].reset();
        $('#logoFileName').val('');
    });
});
</script>
