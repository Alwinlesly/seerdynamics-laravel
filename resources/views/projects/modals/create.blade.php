<div class="modal fade" id="createProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
                <h5 class="modal-title">Create new project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body create-ticket-body">
                <form id="createProjectForm" action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Project ID <span class="req">*</span></label>
                            <input type="text" class="form-control" name="project_id" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Project title <span class="req">*</span></label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <span class="req">*</span></label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Services offered</label>
                        <input type="text" class="form-control" name="services" placeholder="Service1, Service2, ...">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Starting date <span class="req">*</span></label>
                            <input type="date" class="form-control" name="starting_date" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ending date <span class="req">*</span></label>
                            <input type="date" class="form-control" name="ending_date" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Actual start date</label>
                            <input type="date" class="form-control" name="actual_starting_date" value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Actual end date</label>
                            <input type="date" class="form-control" name="actual_ending_date" value="{{ now()->toDateString() }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Project value</label>
                            <input type="number" step="0.01" class="form-control" name="budget">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Project currency</label>
                            <input type="text" class="form-control" name="project_currency" placeholder="USD">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Total hours</label>
                            <input type="number" step="0.01" class="form-control" name="hours">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Contract copy</label>
                            <div class="chat-input py-0">
                                <div class="left-ci d-flex align-items-center gap-0">
                                    <input type="text" class="form-control" id="contractFileName" placeholder="File" readonly>
                                    <input type="file" id="createContractFile" name="contract_copy" class="d-none">
                                    <label for="createContractFile" class="upload-btn mb-0" style="cursor: pointer;">
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
                                <option value="{{ $status->id }}">{{ $status->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Project type</label>
                            <select class="form-select" name="ptype" required>
                                @foreach($project_types as $ptype)
                                <option value="{{ $ptype->id }}">{{ $ptype->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">Project customer <span class="req">*</span></label>
                            <select class="form-select project-modal-select2" name="client_id" required>
                                <option value="">Select customer</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->company ?? $customer->first_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-lg-12">
                            <label class="form-label">Project manager</label>
                            <select class="form-select project-modal-select2" name="project_manager">
                                <option value="">Select manager</option>
                                @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}">{{ $consultant->first_name }} {{ $consultant->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-lg-6 col-pm">
                            <div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" name="is_default" id="defaultProject">
                                    <label class="form-check-label" for="defaultProject">
                                        Is it the default project?
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_visible" id="visibleToCustomer">
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

@push('styles')
<style>
/* Keep Select2 styling unique to Create Project modal only */
#createProjectModal .select2-container {
    width: 100% !important;
}

#createProjectModal .select2-container--default .select2-selection--single {
    height: 42px;
    border: none;
    border-radius: 8px;
    background-color: #F5F5F5;
    display: flex;
    align-items: center;
}

#createProjectModal .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #9A9A9A;
    line-height: 42px;
    padding-left: 12px;
    padding-right: 30px;
}

#createProjectModal .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 42px;
    right: 8px;
}

#createProjectModal .select2-container--default.select2-container--open .select2-selection--single,
#createProjectModal .select2-container--default.select2-container--focus .select2-selection--single {
    border: none;
    box-shadow: none;
}
</style>
@endpush

@push('scripts')
<script>
// Display file name when selected
$(document).on('change', '#createContractFile', function() {
    const fileName = this.files && this.files[0] ? this.files[0].name : '';
    $('#contractFileName').val(fileName);
});

// Ensure date fields default to today whenever modal opens
$('#createProjectModal').on('shown.bs.modal', function() {
    const today = new Date().toISOString().slice(0, 10);
    const $modal = $('#createProjectModal');
    const $form = $('#createProjectForm');

    ['starting_date', 'ending_date', 'actual_starting_date', 'actual_ending_date'].forEach(function(name) {
        const $input = $form.find(`[name="${name}"]`);
        if ($input.length && !$input.val()) {
            $input.val(today);
        }
    });

    // Enable search for customer and manager dropdowns inside modal
    $form.find('.project-modal-select2').each(function() {
        const $select = $(this);
        if (!$select.hasClass('select2-hidden-accessible')) {
            $select.select2({
                dropdownParent: $modal.find('.modal-content'),
                width: '100%',
                minimumResultsForSearch: 0
            });
        }
    });
});

// Create project form submission
$(document).on('submit', '#createProjectForm', function(e) {
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
                showToast('success', response.message);
                $('#createProjectModal').modal('hide');
                loadProjects();
            } else {
                showToast('error', response.message);
            }
        },
        error: function(xhr) {
            showToast('error', xhr.responseJSON?.message || 'Error creating project');
        }
    });
});
</script>
@endpush
