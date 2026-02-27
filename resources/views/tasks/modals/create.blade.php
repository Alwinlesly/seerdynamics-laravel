<!-- Create Task Modal -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
                <h5 class="modal-title" id="createTaskModalLabel">Create new ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body create-ticket-body">
                <form id="createTaskForm">
                    @csrf
                    <div class="mb-3">
                        <label for="title" class="form-label">Ticket title <span class="req">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="req">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="project_id" class="form-label">Project <span class="req">*</span></label>
                            <select class="form-select" id="project_id" name="project_id" required>
                                <option value="">Select project</option>
                                @foreach($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->project_id }} - {{ $project->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="issue_type_id" class="form-label">Issue type <span class="req">*</span></label>
                            <select class="form-select" id="issue_type_id" name="issue_type_id" required>
                                <option value="">Select issue type</option>
                                @foreach($issue_types as $type)
                                    <option value="{{ $type->id }}">{{ $type->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="service" class="form-label">Select service</label>
                            <input type="text" class="form-control" id="service" name="service">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="priority_id" class="form-label">Priority <span class="req">*</span></label>
                            <select class="form-select" id="priority_id" name="priority_id" required>
                                <option value="">Select priority</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority->id }}">{{ $priority->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="issue_date" class="form-label">Issue date <span class="req">*</span></label>
                            <input type="date" class="form-control" id="issue_date" name="issue_date" required>
                        </div>

                        <div class="col-md-4">
                            <label for="status" class="form-label">Status <span class="req">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="">Select status</option>
                                @foreach($task_statuses as $status)
                                    <option value="{{ $status->title }}">{{ $status->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label for="attachment" class="form-label">Attachment/Screenshot</label>
                            <div class="chat-input py-0">
                                <div class="left-ci d-flex align-items-center gap-0">
                                    <input type="text" class="form-control" id="attachmentName" placeholder="File" readonly>
                                    <input type="file" id="attachment" name="attachment" class="d-none" accept="image/*,.pdf,.doc,.docx">
                                    <label for="attachment" class="upload-btn mb-0" style="cursor: pointer;">
                                        <i class="bi bi-upload"></i>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="assigned_consultants" class="form-label">Assign consultants</label>
                        <input type="text" class="form-control" id="assigned_consultants" name="assigned_consultants" placeholder="Comma-separated user IDs">
                    </div>

                    <div class="mb-4 add-mail">
                        <div class="add-mail-left">
                            <label for="additional_mail" class="form-label">Additional mail</label>
                            <input type="email" class="form-control" id="additional_mail" name="additional_mail" placeholder="example@example.com">
                        </div>
                        <div class="add-mail-right">
                            <button type="submit" class="btn btn-primary">Create</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Show file name when file is selected
    $('#attachment').on('change', function() {
        const fileName = this.files[0] ? this.files[0].name : '';
        $('#attachmentName').val(fileName);
    });

    // Submit create form
    $('#createTaskForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        $.ajax({
            url: '/tasks/store',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (!response.error) {
                    $('#createTaskModal').modal('hide');
                    $('#createTaskForm')[0].reset();
                    loadTasks();
                    showToast('success', 'Task created successfully!');
                } else {
                    showToast('error', 'Error: ' + response.message);
                }
            },
            error: function(error) {
                console.error('Error creating task:', error);
                showToast('error', 'Error creating task');
            }
        });
    });
</script>
@endpush
