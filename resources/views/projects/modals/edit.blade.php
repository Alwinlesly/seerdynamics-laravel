<div class="modal fade" id="editProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
                <h5 class="modal-title">Edit project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body create-ticket-body">
                <form id="editProjectForm" action="" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="editProjectId" name="project_id">

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Project ID <span class="req">*</span></label>
                            <input type="text" class="form-control" id="edit_project_id" name="project_id_readonly" readonly>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Project title <span class="req">*</span></label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <span class="req">*</span></label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Services offered</label>
                        <input type="text" class="form-control" id="edit_services" name="services">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Starting date <span class="req">*</span></label>
                            <input type="date" class="form-control" id="edit_starting_date" name="starting_date" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ending date <span class="req">*</span></label>
                            <input type="date" class="form-control" id="edit_ending_date" name="ending_date" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Actual start date</label>
                            <input type="date" class="form-control" id="edit_actual_starting_date" name="actual_starting_date">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Actual end date</label>
                            <input type="date" class="form-control" id="edit_actual_ending_date" name="actual_ending_date">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Project value</label>
                            <input type="number" step="0.01" class="form-control" id="edit_budget" name="budget">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Project currency</label>
                            <input type="text" class="form-control" id="edit_project_currency" name="project_currency" placeholder="USD">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Total hours</label>
                            <input type="number" step="0.01" class="form-control" id="edit_hours" name="hours">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Contract copy</label>
                            <div class="chat-input py-0">
                                <div class="left-ci d-flex align-items-center gap-0">
                                    <input type="text" class="form-control" id="editContractFileName" placeholder="File" readonly>
                                    <input type="file" id="editContractFile" name="contract_copy" class="d-none">
                                    <label for="editContractFile" class="upload-btn mb-0" style="cursor: pointer;">
                                        <i class="bi bi-upload"></i>
                                    </label>
                                </div>
                            </div>
                            <small id="editContractLink" class="d-block mt-1"></small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-lg-3">
                            <label class="form-label">Status <span class="req">*</span></label>
                            <select class="form-select" id="edit_status" name="status" required>
                                @foreach($project_statuses as $status)
                                <option value="{{ $status->id }}">{{ $status->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Project type</label>
                            <select class="form-select" id="edit_ptype" name="ptype" required>
                                @foreach($project_types as $ptype)
                                <option value="{{ $ptype->id }}">{{ $ptype->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Project customer <span class="req">*</span></label>
                            <select class="form-select" id="edit_client_id" name="client_id" required>
                                <option value="">Select customer</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->company ?? $customer->first_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-lg-6">
                            <label class="form-label">Project manager</label>
                            <select class="form-select" id="edit_project_manager" name="project_manager">
                                <option value="">Select manager</option>
                                @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}">{{ $consultant->first_name }} {{ $consultant->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Assign consultants</label>
                            <input type="text" class="form-control" id="edit_assigned_consultants" name="assigned_consultants" placeholder="Comma separated user IDs">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-lg-6 col-pm">
                            <div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="is_default" id="editDefaultProject">
                                    <label class="form-check-label" for="editDefaultProject">
                                        Is it the default project?
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_visible" id="editVisibleToCustomer">
                                    <label class="form-check-label" for="editVisibleToCustomer">
                                        Is it not visible to the customer?
                                    </label>
                                </div>
                            </div>
                            <div class="add-mail-right mt-3">
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
// Display file name when selected (Edit modal)
$(document).on('change', '#editContractFile', function() {
    const fileName = this.files && this.files[0] ? this.files[0].name : '';
    $('#editContractFileName').val(fileName);
});

// Edit project form submission
$(document).on('submit', '#editProjectForm', function(e) {
    e.preventDefault();
    
    const projectId = $('#editProjectId').val();
    const formData = new FormData(this);
    
    $.ajax({
        url: '/projects/' + projectId,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (!response.error) {
                showToast('success', response.message);
                $('#editProjectModal').modal('hide');
                if (typeof loadProjects === 'function') {
                    loadProjects();
                } else {
                    window.location.reload();
                }
            } else {
                showToast('error', response.message);
            }
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            showToast('error', xhr.responseJSON?.message || 'Error updating project');
        }
    });
});
</script>
@endpush
