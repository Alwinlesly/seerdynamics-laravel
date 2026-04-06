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
                                @foreach(($create_projects ?? $projects) as $project)
                                    <option value="{{ $project->id }}">{{ $project->project_id }} - {{ $project->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="issue_type_id" class="form-label">Issue type <span class="req">*</span></label>
                            <select class="form-select" id="issue_type_id" name="issue_type_id" required>
                                <option value="">Select issue type</option>
                                @foreach($issue_types as $type)
                                    <option value="{{ $type->issue_type_id }}">{{ $type->issue_type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label for="service" class="form-label">Select service <span class="req">*</span></label>
                            <select class="form-select" id="service" name="service" required>
                                <option value="">Select Service</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->service }}" data-project="{{ $service->project }}">{{ $service->service }}</option>
                                @endforeach
                            </select>
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
                                @php
                                    $todoStatus = collect($task_statuses)->first(function ($statusItem) {
                                        return strtolower(preg_replace('/[\s_-]+/', '', $statusItem->title)) === 'todo';
                                    });
                                @endphp
                                @if((auth()->user()->inGroup(3) || auth()->user()->inGroup(4)) && $todoStatus)
                                    <option value="{{ $todoStatus->title }}" selected>{{ $todoStatus->title }}</option>
                                @else
                                    <option value="">Select status</option>
                                    @foreach($task_statuses as $status)
                                        @php
                                            $isTodo = strtolower(preg_replace('/[\s_-]+/', '', $status->title)) === 'todo';
                                        @endphp
                                        <option value="{{ $status->title }}" {{ $isTodo ? 'selected' : '' }}>{{ $status->title }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label for="attachment" class="form-label">Attachment/Screenshot</label>
                            <div class="chat-input py-0">
                                <div class="left-ci d-flex align-items-center gap-0">
                                    <input type="text" class="form-control" id="attachmentName" placeholder="Files" readonly>
                                    <input type="file" id="attachment" name="attachment[]" class="d-none" accept="image/*,.pdf,.doc,.docx" multiple>
                                    <label for="attachment" class="upload-btn mb-0" style="cursor: pointer;">
                                        <i class="bi bi-upload"></i>
                                    </label>
                                </div>
                            </div>
                            <div id="attachmentPreview" class="mt-2 d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>

                    @if(auth()->user()->inGroup(1))
                    <div class="mb-3">
                        <label for="users" class="form-label">Assign Consultants <i class="fas fa-question-circle" data-bs-toggle="tooltip" data-bs-placement="right" title="Assign task to the users who will work on this task. Only these users are able to see this task."></i></label>
                        <select name="users[]" id="users" class="form-control select2" multiple>     
                            @foreach($consultant_users as $consultant)
                            <option value="{{ $consultant->id }}">{{ $consultant->first_name }} {{ $consultant->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if(auth()->user()->inGroup(1) || auth()->user()->inGroup(3) || auth()->user()->inGroup(4))
                    <div class="mb-3">
                        <label for="cusers" class="form-label">Assign Users</label>
                        <select name="users[]" id="cusers" class="form-control select2" multiple>      
                            @foreach($other_cusers as $cuser)
                                @if($cuser->id != auth()->id())
                                <option value="{{ $cuser->id }}">{{ $cuser->first_name }} {{ $cuser->last_name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    @endif                    <div class="mb-4 add-mail">
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
#createTaskModal .modal-body {
    position: relative;
}

#attachmentPreview .file-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    border-radius: 12px;
    background: #f0edfa;
    color: #513998;
    font-size: 12px;
    max-width: 100%;
}

#attachmentPreview .file-chip .name {
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

#attachmentPreview .file-chip button {
    border: 0;
    background: transparent;
    color: #513998;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    padding: 0;
}
</style>
<script>
    function createTaskSelect2Parent() {
        return $('#createTaskModal .modal-body');
    }

    let createServiceMasterOptions = [];
    let selectedCreateAttachments = [];

    function createAttachmentKey(file) {
        return [file.name, file.size, file.lastModified].join('__');
    }

    function syncCreateAttachmentInput() {
        const input = document.getElementById('attachment');
        const dataTransfer = new DataTransfer();
        selectedCreateAttachments.forEach(function(file) {
            dataTransfer.items.add(file);
        });
        input.files = dataTransfer.files;
    }

    function renderCreateAttachmentPreview() {
        const $preview = $('#attachmentPreview');
        $preview.empty();

        if (!selectedCreateAttachments.length) {
            $('#attachmentName').val('');
            return;
        }

        if (selectedCreateAttachments.length === 1) {
            $('#attachmentName').val(selectedCreateAttachments[0].name);
        } else {
            $('#attachmentName').val(selectedCreateAttachments.length + ' files selected');
        }

        selectedCreateAttachments.forEach(function(file, index) {
            const $chip = $('<span>', { class: 'file-chip', title: file.name });
            $chip.append($('<span>', { class: 'name', text: file.name }));
            $chip.append($('<button>', {
                type: 'button',
                class: 'remove-create-attachment',
                'data-index': index,
                html: '&times;'
            }));
            $preview.append($chip);
        });
    }

    function cacheCreateServiceOptions() {
        createServiceMasterOptions = $('#service option').not(':first').map(function() {
            return {
                value: String($(this).val() || ''),
                text: String($(this).text() || ''),
                project: String($(this).attr('data-project') || '')
            };
        }).get();
    }

    function initCreateTaskSearchSelects() {
        const $dropdownParent = createTaskSelect2Parent();
        const singleSelector = '#project_id, #issue_type_id, #service, #priority_id, #status';

        $(singleSelector).each(function() {
            const $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }
            const isServiceSelect = $select.attr('id') === 'service';
            $select.select2({
                width: '100%',
                dropdownParent: $dropdownParent,
                minimumResultsForSearch: isServiceSelect ? Infinity : 0
            });
        });
    }

    function filterCreateServicesByProject() {
        const selectedProject = String($('#project_id').val() || '');
        const $service = $('#service');
        const currentValue = String($service.val() || '');

        const seenServices = {};
        const filtered = createServiceMasterOptions.filter(function(opt) {
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

    function setCreateTaskDefaults() {
        const today = new Date().toISOString().split('T')[0];
        $('#issue_date').val(today);

        const $status = $('#status');
        const todoOption = $status.find('option').filter(function() {
            return ($(this).val() || '').toLowerCase().replace(/[\s_-]/g, '') === 'todo';
        }).first();

        if (todoOption.length) {
            $status.val(todoOption.val()).trigger('change.select2');
        }
    }

    // Initialize select2
    $(document).ready(function() {
        cacheCreateServiceOptions();

        $('#users, #cusers').select2({
            width: '100%',
            placeholder: "Select users",
            allowClear: true
        });
        initCreateTaskSearchSelects();

        $('#project_id').on('change', filterCreateServicesByProject);

        $('#createTaskModal').on('show.bs.modal', function() {
            const pageProject = $('#projectFilter').val();
            if (pageProject && !$('#project_id').val()) {
                $('#project_id').val(pageProject);
            }
            if (!createServiceMasterOptions.length) {
                cacheCreateServiceOptions();
            }
            initCreateTaskSearchSelects();
            filterCreateServicesByProject();
            setCreateTaskDefaults();
        });

        filterCreateServicesByProject();
        setCreateTaskDefaults();
    });

    // Keep adding files across multiple picks (no need to hold Ctrl)
    $(document).on('change', '#attachment', function() {
        const incoming = Array.from(this.files || []);
        if (!incoming.length) return;

        const existingKeys = new Set(selectedCreateAttachments.map(createAttachmentKey));
        incoming.forEach(function(file) {
            const key = createAttachmentKey(file);
            if (!existingKeys.has(key)) {
                selectedCreateAttachments.push(file);
                existingKeys.add(key);
            }
        });

        syncCreateAttachmentInput();
        renderCreateAttachmentPreview();
    });

    $(document).on('click', '.remove-create-attachment', function() {
        const index = Number($(this).data('index'));
        if (Number.isInteger(index) && index >= 0 && index < selectedCreateAttachments.length) {
            selectedCreateAttachments.splice(index, 1);
            syncCreateAttachmentInput();
            renderCreateAttachmentPreview();
        }
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
                    setCreateTaskDefaults();
                    // Reset select2 fields if any
                    $('#createTaskForm .select2').val(null).trigger('change');
                    selectedCreateAttachments = [];
                    syncCreateAttachmentInput();
                    renderCreateAttachmentPreview();
                    loadTasks();
                    showToast('success', 'Task created successfully!');
                } else {
                    showToast('error', 'Error: ' + response.message);
                }
            },
            error: function(xhr) {
                console.error('Error creating task:', xhr);
                let errorMessage = 'Error creating task';
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

    $('#createTaskModal').on('hidden.bs.modal', function() {
        selectedCreateAttachments = [];
        syncCreateAttachmentInput();
        renderCreateAttachmentPreview();
    });
</script>
@endpush
