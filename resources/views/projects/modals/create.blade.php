<div class="modal fade" id="createProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
                <h5 class="modal-title">Create new project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body create-ticket-body">
                <form id="createProjectForm" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Project title <span class="req">*</span></label>
                        <input type="text" class="form-control" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <span class="req">*</span></label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Services offered <span class="req">*</span></label>
                        <input type="text" class="form-control" name="services_offered" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Starting date <span class="req">*</span></label>
                            <input type="date" class="form-control" name="starting_date" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ending date <span class="req">*</span></label>
                            <input type="date" class="form-control" name="ending_date" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Actual start date</label>
                            <input type="date" class="form-control" name="actual_starting_date">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Actual end date</label>
                            <input type="date" class="form-control" name="actual_ending_date">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Project value</label>
                            <input type="number" step="0.01" class="form-control" name="project_value">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Project currency</label>
                            <input type="text" class="form-control" name="project_currency" placeholder="USD">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Total hours</label>
                            <input type="number" step="0.01" class="form-control" name="total_hours">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Contract copy</label>
                            <div class="chat-input py-0">
                                <div class="left-ci d-flex align-items-center gap-0">
                                    <input type="text" class="form-control" id="contractFileName" placeholder="File" readonly>
                                    <input type="file" id="contractFile" name="contract_copy" class="d-none">
                                    <label for="contractFile" class="upload-btn mb-0" style="cursor: pointer;">
                                        <i class="bi bi-upload"></i>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-lg-3">
                            <label class="form-label">Status <span class="req">*</span></label>
                            <select class="form-select" name="status" required>
                                @foreach($project_statuses as $status)
                                <option value="{{ $status->title }}">{{ $status->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Project type</label>
                            <select class="form-select" name="project_type">
                                <option value="">Select type</option>
                                <option value="Support">Support</option>
                                <option value="Implementation">Implementation</option>
                                <option value="Consulting">Consulting</option>
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Project customer <span class="req">*</span></label>
                            <select class="form-select" name="client_id" required>
                                <option value="">Select customer</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->company ?? $customer->first_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <label class="form-label">Assign consultants</label>
                            <input type="text" class="form-control" name="assigned_consultants" placeholder="Comma separated user IDs">
                        </div>
                        <div class="col-lg-6 col-pm">
                            <div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="is_default" id="defaultProject">
                                    <label class="form-check-label" for="defaultProject">
                                        Is it the default project?
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_not_visible_to_customer" id="visibleToCustomer">
                                    <label class="form-check-label" for="visibleToCustomer">
                                        Is it not visible to the customer?
                                    </label>
                                </div>
                            </div>
                            <div class="add-mail-right mt-3">
                                <button type="submit" class="btn btn-primary">Create</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Display file name when selected
$('#contractFile').on('change', function() {
    const fileName = $(this).val().split('\\').pop();
    $('#contractFileName').val(fileName);
});

// Create project form submission
$('#createProjectForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: '{{ route("projects.store") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (!response.error) {
                alert(response.message);
                $('#createProjectModal').modal('hide');
                loadProjects();
            } else {
                alert(response.message);
            }
        },
        error: function(xhr) {
            alert('Error creating project');
        }
    });
});
</script>
