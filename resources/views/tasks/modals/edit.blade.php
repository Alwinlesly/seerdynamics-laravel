<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
                <h5 class="modal-title" id="editTaskModalLabel">Edit ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body create-ticket-body">
                <form id="editTaskForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_task_id" name="task_id">
                    <input type="hidden" id="edit_project_id" name="project_id">

                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Ticket title <span class="req">*</span></label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description <span class="req">*</span></label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="edit_issue_type_id" class="form-label">Issue type <span class="req">*</span></label>
                            <select class="form-select" id="edit_issue_type_id" name="issue_type_id" required>
                                <option value="">Select issue type</option>
                                @foreach($issue_types as $type)
                                    <option value="{{ $type->issue_type_id }}">{{ $type->issue_type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_service" class="form-label">Select service <span class="req">*</span></label>
                            <select class="form-select" id="edit_service" name="service" required>
                                <option value="">Select Service</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->service }}" data-project="{{ $service->project }}">{{ $service->service }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_priority_id" class="form-label">Priority <span class="req">*</span></label>
                            <select class="form-select" id="edit_priority_id" name="priority_id" required>
                                <option value="">Select priority</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority->id }}">{{ $priority->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="edit_issue_date" class="form-label">Issue date <span class="req">*</span></label>
                            <input type="date" class="form-control" id="edit_issue_date" name="issue_date" required>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_attachment" class="form-label">Attachment/Screenshot</label>
                            <div class="chat-input py-0">
                                <div class="left-ci d-flex align-items-center gap-0">
                                    <input type="text" class="form-control" id="editAttachmentName" placeholder="File" readonly>
                                    <input type="file" id="edit_attachment" name="attachment" class="d-none" accept="image/*,.pdf,.doc,.docx">
                                    <label for="edit_attachment" class="upload-btn mb-0" style="cursor: pointer;">
                                        <i class="bi bi-upload"></i>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label for="edit_status" class="form-label">Status <span class="req">*</span></label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="">Select status</option>
                                @foreach($task_statuses as $status)
                                    <option value="{{ $status->title }}">{{ $status->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if(auth()->user()->inGroup(1))
                    <div class="mb-3">
                        <label for="edit_users" class="form-label">Assign Consultants <i class="fas fa-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="Assign task to the users who will work on this task. Only these users are able to see this task."></i></label>
                        <select name="users[]" id="edit_users" class="form-control select2" multiple>     
                            @foreach($consultant_users as $consultant)
                            <option value="{{ $consultant->id }}">{{ $consultant->first_name }} {{ $consultant->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if(auth()->user()->inGroup(1) || auth()->user()->inGroup(3) || auth()->user()->inGroup(4))
                    <div class="mb-3">
                        <label for="edit_cusers" class="form-label">Assign Users</label>
                        <select name="users[]" id="edit_cusers" class="form-control select2" multiple>      
                            @foreach($other_cusers as $cuser)
                                @if($cuser->id != auth()->id())
                                <option value="{{ $cuser->id }}">{{ $cuser->first_name }} {{ $cuser->last_name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    @endif                    <div class="mb-4 add-mail">
                        <div class="add-mail-left">
                            <label for="edit_additional_mail" class="form-label">Additional mail</label>
                            <input type="email" class="form-control" id="edit_additional_mail" name="additional_mail" placeholder="example@example.com">
                        </div>
                        <div class="add-mail-right">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<style>
/* Base input styling matching Codeigniter components.css */
.select2-container .select2-selection--multiple, .select2-container .select2-selection--single {
    box-sizing: border-box;
    cursor: pointer;
    display: block;
    min-height: 42px;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    -webkit-user-select: none;
    outline: none;
    background-color: #fdfdff;
    border-color: #e4e6fc;
}

/* Modal z-index fix */
.select2-container--open {
    z-index: 9999999;
}

/* Tag styles */
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #e52165;
    color: #fff;
    border: none;
    border-radius: 3px;
    padding: 5px 10px;
    margin-top: 5px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #fff;
    margin-right: 5px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #f8f9fa;
    background: transparent;
}
.select2-container--default .select2-results__option[aria-selected=true],
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #e52165;
    color: #fff;
}

/* Keep Select2 dropdown aligned with fields inside scrolling modal body */
#editTaskModal .modal-body {
    position: relative;
}
</style>
<script>
    function editTaskSelect2Parent() {
        return $('#editTaskModal .modal-body');
    }

    let editServiceMasterOptions = [];

    function cacheEditServiceOptions() {
        editServiceMasterOptions = $('#edit_service option').not(':first').map(function() {
            return {
                value: String($(this).val() || ''),
                text: String($(this).text() || ''),
                project: String($(this).attr('data-project') || '')
            };
        }).get();
    }

    function initEditTaskSearchSelects() {
        const $dropdownParent = editTaskSelect2Parent();
        const singleSelector = '#edit_issue_type_id, #edit_service, #edit_priority_id, #edit_status';

        $(singleSelector).each(function() {
            const $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }
            const isServiceSelect = $select.attr('id') === 'edit_service';
            $select.select2({
                width: '100%',
                dropdownParent: $dropdownParent,
                minimumResultsForSearch: isServiceSelect ? Infinity : 0
            });
        });
    }

    function filterEditServicesByProject() {
        const selectedProject = String($('#edit_project_id').val() || '');
        const $service = $('#edit_service');
        const currentValue = String($service.val() || '');

        const seenServices = {};
        const filtered = editServiceMasterOptions.filter(function(opt) {
            if (selectedProject === '' || opt.project !== selectedProject) {
                return false;
            }
            const normalized = opt.text.trim().toLowerCase();
            if (normalized && seenServices[normalized]) {
                return false;
            }
            seenServices[normalized] = true;
            return true;
        });

        $service.find('option').not(':first').remove();
        filtered.forEach(function(opt) {
            $service.append(
                $('<option>', { value: opt.value, text: opt.text }).attr('data-project', opt.project)
            );
        });

        if (currentValue && filtered.some(function(opt) { return opt.value === currentValue; })) {
            $service.val(currentValue);
        } else {
            $service.val('');
        }

        // Keep Select2 in sync without destroy/re-init (prevents UI glitching).
        $service.trigger('change.select2');
    }

    // Initialize select2
    $(document).ready(function() {
        cacheEditServiceOptions();

        $('#edit_users, #edit_cusers').select2({
            width: '100%',
            placeholder: "Select users",
            allowClear: true
        });
        initEditTaskSearchSelects();

        $('#edit_project_id').on('change', filterEditServicesByProject);
        $('#editTaskModal').on('shown.bs.modal', function() {
            if (!editServiceMasterOptions.length) {
                cacheEditServiceOptions();
            }
            initEditTaskSearchSelects();
            filterEditServicesByProject();
        });
        filterEditServicesByProject();
    });

    // Show file name when file is selected
    $(document).on('change', '#edit_attachment', function() {
        const fileName = this.files[0] ? this.files[0].name : '';
        $('#editAttachmentName').val(fileName);
    });

    // Submit edit form
    $('#editTaskForm').on('submit', function(e) {
        e.preventDefault();

        const taskId = $('#edit_task_id').val();
        const formData = new FormData(this);

        $.ajax({
            url: `/tasks/${taskId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (!response.error) {
                    $('#editTaskModal').modal('hide');
                    loadTasks();
                    showToast('success', 'Task updated successfully!');
                } else {
                    showToast('error', 'Error: ' + response.message);
                }
            },
            error: function(xhr) {
                console.error('Error updating task:', xhr);
                let errorMessage = 'Error updating task';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                    if (xhr.responseJSON.errors) {
                        errorMessage += '<br>' + Object.values(xhr.responseJSON.errors).map(e => e.join(', ')).join('<br>');
                    }
                }
                showToast('error', errorMessage);
            }
        });
    });
</script>
@endpush
