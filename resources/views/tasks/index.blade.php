@extends('layouts.app')

@section('content')
<div class="main">
    <!-- Top Header -->
    @include('partials.header')

    <!-- Main Content -->
    <div class="row m-0">
        @include('partials.sidebar')

        <div class="right-section col-lg-9 px-0 pb-0">
            <div class="px-4">
                <div class="header">
                    <h1 class="pg-hd"><b>Tickets</b></h1>
                    <div class="search-create">
                        <div class="search-wrap">
                            <input type="search" class="form-control form-control-sm py-2" placeholder="Search" id="searchInput" />
                            <span class="search-icon d-flex">
                                <svg fill="#9A9A9A" width="15px" height="15px" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M21.71,20.29,18,16.61A9,9,0,1,0,16.61,18l3.68,3.68a1,1,0,0,0,1.42,0A1,1,0,0,0,21.71,20.29ZM11,18a7,7,0,1,1,7-7A7,7,0,0,1,11,18Z" />
                                </svg>
                            </span>
                        </div>

                        @if(!auth()->user()->inGroup(2))
                        <button class="btn btn-create" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                            <span>
                                <svg fill="#fff" width="20px" height="20px" viewBox="0 0 24 24" id="plus"
                                    data-name="Flat Color" xmlns="http://www.w3.org/2000/svg" class="icon flat-color">
                                    <path id="primary"
                                        d="M12,20a1,1,0,0,1-1-1V13H5a1,1,0,0,1,0-2h6V5a1,1,0,0,1,2,0v6h6a1,1,0,0,1,0,2H13v6A1,1,0,0,1,12,20Z"
                                        style="fill: #fff"></path>
                                </svg>
                            </span> Create
                        </button>
                        @endif
                    </div>
                </div>

                <div class="ticket-status-summary" id="ticketStatusSummary"></div>

                <div class="sel-wrapper filters-initializing" id="taskFiltersRow">
                    @if(!auth()->user()->inGroup(4))
                    <select class="form-select searchable-filter" id="customerFilter">
                        <option value="">Customer</option>
                        @if(isset($customers))
                            @foreach($customers as $customer)
                                @php
                                    $customerCompany = trim((string) ($customer->company ?? ''));
                                    $customerFullName = trim((string) (($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')));
                                @endphp
                                <option value="{{ $customer->id }}">{{ $customerCompany !== '' ? $customerCompany : $customerFullName }}</option>
                            @endforeach
                        @endif
                    </select>
                    @endif

                    <select class="form-select searchable-filter" id="projectTypeFilter">
                        <option value="">Project type</option>
                        @foreach(($project_types ?? []) as $ptype)
                            <option value="{{ $ptype->id }}">{{ $ptype->title }}</option>
                        @endforeach
                    </select>

                    <select class="form-select searchable-filter" id="projectFilter">
                        <option value="">Project</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}" data-customer="{{ $project->client_id }}" data-ptype="{{ $project->ptype }}">{{ $project->project_id }} - {{ $project->title }}</option>
                        @endforeach
                    </select>

                    <select class="form-select searchable-filter" id="statusFilter">
                        <option value="">Status</option>
                        @foreach($task_statuses as $status)
                            <option value="{{ $status->title }}">{{ $status->title }}</option>
                        @endforeach
                    </select>

                    <select class="form-select searchable-filter" id="priorityFilter">
                        <option value="">Priority</option>
                        @foreach($priorities as $priority)
                            <option value="{{ $priority->id }}">{{ $priority->title }}</option>
                        @endforeach
                    </select>

                    <select class="form-select searchable-filter" id="sortFilter">
                        <option value="">Sort</option>
                        <option value="id">Latest</option>
                        <option value="title">Title</option>
                        <option value="created">Date</option>
                    </select>

                    <button class="btn btn-sm btn-download" id="downloadBtn">
                        <span>
                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M12 2a1 1 0 0 1 1 1v10.586l2.293-2.293a1 1 0 0 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 1 1 1.414-1.414L11 13.586V3a1 1 0 0 1 1-1zM5 17a1 1 0 0 1 1 1v2h12v-2a1 1 0 1 1 2 0v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a1 1 0 0 1 1-1z"
                                    fill="#fff" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div>
                <div class="p-4 pb-100">
                    <div class="table-responsive table-x">
                        <table class="table table-bordered align-middle mb-0 my-table">
                            <thead>
                                <tr>
                                    <th>Ticket Id</th>
                                    <th>Ticket name</th>
                                    <th class="prj-tkt">Project</th>
                                    <th>Customer</th>
                                    <th>Estimate</th>
                                    <th>Priority</th>
                                    <th>Created By</th>
                                    <th>Created Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="tasksTableBody">
                                <tr>
                                    <td colspan="10" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="d-flex align-items-center gap-2 pagination">
                            <span class="current-total" id="currentPage">1</span>
                            <span class="text-muted">of <span id="totalPages">1</span></span>
                            <div class="d-flex">
                                <button class="btn btn-light d-flex align-items-center" id="prevBtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="bi bi-chevron-left" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd"
                                            d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0" />
                                    </svg>
                                </button>

                                <button class="btn btn-light d-flex align-items-center next-btn" id="nextBtn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                                        class="bi bi-chevron-right" viewBox="0 0 16 16">
                                        <path fill-rule="evenodd"
                                            d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                @include('partials.footer')
            </div>
        </div>
    </div>
</div>

<!-- Include Modals -->
@include('tasks.modals.create')
@include('tasks.modals.edit')
@include('tasks.modals.delete')
@include('tasks.modals.detail')

@endsection

@push('styles')
<style>
/* Keep task detail attachment filenames inside modal box */
.task-attachment-item > div {
    align-items: flex-start;
}

.task-attachment-list {
    min-width: 0;
    max-width: 100%;
    overflow: hidden;
    gap: 4px;
}

.task-attachment-link {
    display: block;
    max-width: 100%;
    overflow-wrap: anywhere;
    word-break: break-word;
    white-space: normal;
}

#commentAttachmentPreview .file-chip {
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

#commentAttachmentPreview .file-chip .name {
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

#commentAttachmentPreview .file-chip button {
    border: 0;
    background: transparent;
    color: #513998;
    font-weight: 700;
    line-height: 1;
    cursor: pointer;
    padding: 0;
}

#addCommentForm .comment-upload-row {
    position: relative;
}

#addCommentForm .comment-upload-row .upload-trigger {
    z-index: 2;
}

.ticket-status-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 4px 0 14px;
}

.status-summary-chip {
    border: 1px solid #503897;
    border-radius: 8px;
    background: #fff;
    color: #6f6f6f;
    font-size: 15px;
    font-weight: 600;
    line-height: 1.2;
    padding: 6px 12px;
    cursor: pointer;
}

.status-summary-chip.active {
    background: #503897;
    color: #fff;
}

.sel-wrapper .select2-container {
    min-width: 0;
    width: auto !important;
    flex: 1 1 170px;
}

.sel-wrapper .select2-container .select2-selection--single {
    height: 42px;
    border: none;
    border-radius: 8px;
    background-color: #F5F5F5;
    display: flex;
    align-items: center;
}

.sel-wrapper .select2-container .select2-selection__rendered {
    color: #9A9A9A;
    line-height: 40px;
    padding-left: 12px;
    padding-right: 28px;
}

.sel-wrapper .select2-container .select2-selection__arrow {
    height: 40px;
    right: 8px;
}

.sel-wrapper .select2-container--default.select2-container--open .select2-selection--single,
.sel-wrapper .select2-container--default.select2-container--focus .select2-selection--single {
    border: none;
    box-shadow: none;
}

.right-section .sel-wrapper {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start;
    gap: 12px;
    width: 100%;
}

.right-section .sel-wrapper .btn-download {
    flex: 0 0 auto;
}

/* Avoid FOUC on refresh: show filters only after Select2 init completes */
.right-section .sel-wrapper.filters-initializing {
    visibility: hidden;
    opacity: 0;
}

.right-section .sel-wrapper.filters-ready {
    visibility: visible;
    opacity: 1;
}

/* Keep ticket status chips readable in listing table */
.my-table th:nth-child(9),
.my-table td:nth-child(9) {
    width: 140px;
    padding-left: 8px;
    padding-right: 8px;
}

#tasksTableBody .status {
    display: inline-block;
    max-width: 100%;
    white-space: normal;
    word-break: normal;
    overflow-wrap: normal;
}

#tasksTableBody .ticket-link {
    color: #513998;
    text-decoration: none;
    cursor: pointer;
}

#tasksTableBody .ticket-link:hover,
#tasksTableBody .ticket-link:focus {
    color: #3e2d79;
}

#tasksTableBody td.estimate-approved {
    color: #2ab700 !important;
    font-weight: 600;
}

.my-table th:nth-child(10),
.my-table td:nth-child(10) {
    width: 120px;
    white-space: nowrap;
    text-align: center;
    padding-left: 8px;
    padding-right: 8px;
}

#tasksTableBody .action-group {
    gap: 8px !important;
}

#tasksTableBody .close-ticket-popup {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 12px;
    border: 1px solid #503897;
    border-radius: 8px;
    background: #503897;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.2;
    white-space: nowrap;
    text-decoration: none;
    cursor: pointer;
}

#tasksTableBody .close-ticket-popup:hover {
    background: #462f84;
    border-color: #462f84;
    color: #fff;
}

@media (max-width: 991.98px) {
    .right-section .sel-wrapper .select2-container,
    .right-section .sel-wrapper > .searchable-filter {
        flex: 1 1 100%;
    }
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #503897 !important;
    color: #fff !important;
}

.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #ece7fb !important;
    color: #2b2b2b !important;
}

.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: #503897 !important;
    color: #fff !important;
}
</style>
@endpush

@push('scripts')
<script>
    const canDeleteTaskAction = @json(auth()->user()->inGroup(1) || permissions('task_delete'));
    const isConsultantUser = @json(auth()->user()->inGroup(2));
    const canEditTaskAction = @json((auth()->user()->inGroup(1) || permissions('task_edit')) && !auth()->user()->inGroup(3) && !auth()->user()->inGroup(4));
    const canCloseTaskAction = @json(auth()->user()->inGroup(3) || auth()->user()->inGroup(4));
    let currentPage = 1;
    let totalRecords = 0;
    const limit = 20;
    let selectedCommentAttachments = [];
    let allProjectFilterOptions = [];
    let allProjectTypeFilterOptions = [];

    function commentAttachmentKey(file) {
        return [file.name, file.size, file.lastModified].join('__');
    }

    function syncCommentAttachmentInput() {
        const input = document.getElementById('commentFileInput');
        if (!input) return;
        const dataTransfer = new DataTransfer();
        selectedCommentAttachments.forEach(function(file) {
            dataTransfer.items.add(file);
        });
        input.files = dataTransfer.files;
    }

    function renderCommentAttachmentPreview() {
        const $preview = $('#commentAttachmentPreview');
        const $display = $('#fileNameDisplay');
        if (!$preview.length || !$display.length) return;

        $preview.empty();

        if (!selectedCommentAttachments.length) {
            $display.val('');
            return;
        }

        if (selectedCommentAttachments.length === 1) {
            $display.val(selectedCommentAttachments[0].name);
        } else {
            $display.val(selectedCommentAttachments.length + ' files selected');
        }

        selectedCommentAttachments.forEach(function(file, index) {
            const $chip = $('<span>', { class: 'file-chip', title: file.name });
            $chip.append($('<span>', { class: 'name', text: file.name }));
            $chip.append($('<button>', {
                type: 'button',
                class: 'remove-comment-attachment',
                'data-index': index,
                html: '&times;'
            }));
            $preview.append($chip);
        });
    }

    $(document).ready(function() {
        // Match existing project behavior: searchable filter dropdowns.
        try {
            $('.searchable-filter').select2({
                width: 'resolve',
                minimumResultsForSearch: 0
            });
        } finally {
            $('#taskFiltersRow')
                .removeClass('filters-initializing')
                .addClass('filters-ready');
        }

        // Keep original option sets to support dependent filtering.
        allProjectFilterOptions = $('#projectFilter option').clone();
        allProjectTypeFilterOptions = $('#projectTypeFilter option').clone();

        // Check if project filter is passed in URL
        const urlParams = new URLSearchParams(window.location.search);
        const projectId = urlParams.get('project');
        if (projectId) {
            $('#projectFilter').val(projectId);
        }

        syncTaskDependentFilters();
        
        loadTasks();

        // Search (input/search covers typing + clear button)
        $('#searchInput').on('input search', function() {
            currentPage = 1;
            loadTasks();
        });

        // Filters
        $('#customerFilter').on('change', function() {
            syncTaskDependentFilters();
            currentPage = 1;
            loadTasks();
        });

        $('#projectFilter, #statusFilter, #priorityFilter, #sortFilter').on('change', function() {
            currentPage = 1;
            loadTasks();
        });

        $('#projectTypeFilter').on('change', function() {
            syncTaskDependentFilters();
            currentPage = 1;
            loadTasks();
        });

        // Summary chip click -> apply status filter and reload
        $(document).on('click', '.status-summary-chip', function() {
            const statusTitle = String($(this).data('status') || '');
            $('#statusFilter').val(statusTitle).trigger('change');
        });

        // Pagination
        $('#prevBtn').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadTasks();
            }
        });

        $('#nextBtn').on('click', function() {
            if (currentPage < Math.ceil(totalRecords / limit)) {
                currentPage++;
                loadTasks();
            }
        });

        // Download button
        $('#downloadBtn').on('click', function() {
            const search = $('#searchInput').val();
            const customer = $('#customerFilter').val();
            const projectType = $('#projectTypeFilter').val();
            const project = $('#projectFilter').val();
            const status = $('#statusFilter').val();
            const priority = $('#priorityFilter').val();
            const sort = $('#sortFilter').val();

            // Build query string
            let params = new URLSearchParams();
            if (search) params.append('search', search);
            if (customer) params.append('customer', customer);
            if (projectType) params.append('project_type', projectType);
            if (project) params.append('project', project);
            if (status) params.append('status', status);
            if (priority) params.append('priority', priority);
            if (sort) params.append('sort', sort);

            // Redirect to export URL with filters
            window.location.href = '/tasks/export?' + params.toString();
        });
    });

    function loadTasks() {
        const search = $('#searchInput').val();
        const customer = $('#customerFilter').val();
        const projectType = $('#projectTypeFilter').val();
        const project = $('#projectFilter').val();
        const status = $('#statusFilter').val();
        const priority = $('#priorityFilter').val();
        const sort = $('#sortFilter').val();
        const offset = (currentPage - 1) * limit;

        $('#tasksTableBody').html('<tr><td colspan="10" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: '/tasks/list',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                search: search,
                customer: customer,
                project_type: projectType,
                project: project,
                status: status,
                priority: priority,
                sort: sort,
                limit: limit,
                offset: offset
            },
            success: function(response) {
                totalRecords = response.total;
                renderTasks(response.rows);
                renderStatusSummary(response.status_summary || []);
                updatePagination();
            },
            error: function(error) {
                console.error('Error loading tasks:', error);
                $('#tasksTableBody').html('<tr><td colspan="10" class="text-center">Error loading tasks</td></tr>');
            }
        });
    }

    function renderTasks(tasks) {
        let html = '';

        if (tasks.length === 0) {
            html = '<tr><td colspan="10" class="text-center">No tasks found</td></tr>';
        } else {
            tasks.forEach(task => {
                const statusClass = task.status.toLowerCase().replace(/\s+/g, '-');
                const normalizedTaskStatus = String(task.status || '').toLowerCase().replace(/[\s_-]+/g, '');
                const canShowClose = canCloseTaskAction && (normalizedTaskStatus === 'completed' || normalizedTaskStatus === 'onhold');
                const priorityClass = task.priority_class;
                const actionHtml = `
                    ${canEditTaskAction ? `
                        <span class="edit-task" data-id="${task.id}" style="cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7d6bb2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                            </svg>
                        </span>
                    ` : ''}
                    ${canDeleteTaskAction ? `
                        <span class="delete-task" data-id="${task.id}" style="cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 24 24" fill="none" stroke="#7d6bb2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"/>
                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                <line x1="10" y1="11" x2="10" y2="17"/>
                                <line x1="14" y1="11" x2="14" y2="17"/>
                                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                            </svg>
                        </span>
                    ` : ''}
                    ${canShowClose ? `
                        <span class="close-ticket-popup" data-id="${task.id}" title="Close Ticket">
                            <span>Close ticket</span>
                        </span>
                    ` : ''}
                `;

                html += `
                    <tr>
                        <td>${task.ticket_id}</td>
                        <td><a href="#" class="view-task ticket-link" data-id="${task.id}">${task.title}</a></td>
                        <td>${task.project}</td>
                        <td>${task.customer}</td>
                        <td class="text-center ${task.is_estimate_approved ? 'estimate-approved' : ''}">${task.estimate}</td>
                        <td><span class="${priorityClass}-pr">${task.priority}</span></td>
                        <td>${task.created_by}</td>
                        <td>${task.created_date}</td>
                        <td><span class="status ${statusClass}">${task.status}</span></td>
                        <td>
                            <div class="d-flex align-items-center justify-content-center action-group">
                                ${actionHtml}
                            </div>
                        </td>
                    </tr>
                `;
            });
        }

        $('#tasksTableBody').html(html);
    }

    function renderStatusSummary(statusSummary) {
        const $summary = $('#ticketStatusSummary');
        if (!$summary.length) return;

        if (!Array.isArray(statusSummary) || statusSummary.length === 0) {
            $summary.html('');
            return;
        }

        const selectedStatus = String($('#statusFilter').val() || '').trim().toLowerCase();
        let html = '';
        statusSummary.forEach(function(item) {
            const title = String(item.title || '').trim();
            const count = Number(item.count || 0);
            const isActive = title.toLowerCase() === selectedStatus;
            html += `
                <button type="button" class="status-summary-chip ${isActive ? 'active' : ''}" data-status="${title}">
                    ${title}(${count})
                </button>
            `;
        });
        $summary.html(html);
    }

    function updatePagination() {
        const totalPages = Math.ceil(totalRecords / limit);
        $('#currentPage').text(currentPage);
        $('#totalPages').text(totalPages);

        $('#prevBtn').prop('disabled', currentPage === 1);
        $('#nextBtn').prop('disabled', currentPage >= totalPages);
    }

    // View task details
    $(document).on('click', '.view-task', function(e) {
        e.preventDefault();
        const taskId = $(this).data('id');
        
        // Show loading state
        $('#taskDetailContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);
        
        // Open modal
        $('#taskDetailModal').modal('show');
        
        // Set task ID on timer button
        $('#timerBtn').data('task-id', taskId);
        
        // Load task details
        $.ajax({
            url: `/tasks/${taskId}`,
            method: 'GET',
            success: function(response) {
                if (!response.error) {
                    $('#taskDetailContent').html(response.html);
                    selectedCommentAttachments = [];
                    syncCommentAttachmentInput();
                    renderCommentAttachmentPreview();
                    // Mirror CI flow: customer admin cannot see timer controls in ticket details.
                    if (response.can_see_time) {
                        $('#timerBtn').removeClass('d-none');
                        checkTimerStatus(taskId);
                    } else {
                        $('#timerBtn').addClass('d-none');
                        stopTimerDisplay();
                    }
                    loadEstimates(taskId, !!response.can_add_estimate);
                } else {
                    $('#taskDetailContent').html(`
                        <div class="alert alert-danger">Failed to load task details</div>
                    `);
                }
            },
            error: function() {
                $('#taskDetailContent').html(`
                    <div class="alert alert-danger">Failed to load task details</div>
                `);
            }
        });
    });

    // Handle message attachment selection (cumulative)
    $(document).on('change', '#commentFileInput', function() {
        const incoming = Array.from(this.files || []);
        if (!incoming.length) return;

        const existingKeys = new Set(selectedCommentAttachments.map(commentAttachmentKey));
        incoming.forEach(function(file) {
            const key = commentAttachmentKey(file);
            if (!existingKeys.has(key)) {
                selectedCommentAttachments.push(file);
                existingKeys.add(key);
            }
        });

        syncCommentAttachmentInput();
        renderCommentAttachmentPreview();
    });

    function syncTaskDependentFilters() {
        const selectedCustomer = String($('#customerFilter').val() || '');
        const selectedProjectType = String($('#projectTypeFilter').val() || '');

        // Filter project type dropdown by selected customer.
        // This mirrors existing app behavior: customer selection narrows downstream filters.
        const visibleProjectTypeIds = {};
        allProjectFilterOptions.each(function() {
            const optionValue = String($(this).attr('value') || '');
            if (optionValue === '') return;
            const optionCustomer = String($(this).data('customer') || '');
            if (selectedCustomer !== '' && optionCustomer !== selectedCustomer) return;
            const typeId = String($(this).data('ptype') || '');
            if (typeId !== '') {
                visibleProjectTypeIds[typeId] = true;
            }
        });

        const currentProjectType = String($('#projectTypeFilter').val() || '');
        $('#projectTypeFilter').empty();
        allProjectTypeFilterOptions.each(function() {
            const optionValue = String($(this).attr('value') || '');
            if (optionValue === '' || selectedCustomer === '' || visibleProjectTypeIds[optionValue]) {
                $('#projectTypeFilter').append($(this).clone());
            }
        });

        if ($('#projectTypeFilter option[value="' + currentProjectType + '"]').length > 0) {
            $('#projectTypeFilter').val(currentProjectType);
        } else {
            $('#projectTypeFilter').val('');
        }
        $('#projectTypeFilter').trigger('change.select2');

        // Filter project dropdown by selected customer + selected project type.
        const activeProjectType = String($('#projectTypeFilter').val() || '');
        const currentProject = String($('#projectFilter').val() || '');
        $('#projectFilter').empty();
        allProjectFilterOptions.each(function() {
            const optionValue = String($(this).attr('value') || '');
            if (optionValue === '') {
                $('#projectFilter').append($(this).clone());
                return;
            }
            const optionCustomer = String($(this).data('customer') || '');
            const optionType = String($(this).data('ptype') || '');
            const customerMatch = selectedCustomer === '' || optionCustomer === selectedCustomer;
            const typeMatch = activeProjectType === '' || optionType === activeProjectType;
            if (customerMatch && typeMatch) {
                $('#projectFilter').append($(this).clone());
            }
        });

        if ($('#projectFilter option[value="' + currentProject + '"]').length > 0) {
            $('#projectFilter').val(currentProject);
        } else {
            $('#projectFilter').val('');
        }
        $('#projectFilter').trigger('change.select2');
    }

    $(document).on('click', '.remove-comment-attachment', function() {
        const index = Number($(this).data('index'));
        if (Number.isInteger(index) && index >= 0 && index < selectedCommentAttachments.length) {
            selectedCommentAttachments.splice(index, 1);
            syncCommentAttachmentInput();
            renderCommentAttachmentPreview();
        }
    });

    // Handle comment submission
    $(document).on('submit', '#addCommentForm', function(e) {
        e.preventDefault();
        const taskId = $(this).data('task-id');
        const formData = new FormData(this);
        
        $.ajax({
            url: `/tasks/${taskId}/comments`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (!response.error) {
                    selectedCommentAttachments = [];
                    // Reload task detail to show new comment
                    $('.view-task[data-id="' + taskId + '"]').click();
                    showToast('success', 'Comment added successfully');
                }
            },
            error: function() {
                showToast('error', 'Failed to add comment');
            }
        });
    });

    // Estimate calculations + form submit
    $(document).on('input', '#estimateFunc, #estimateTech', function() {
        const functional = parseFloat($('#estimateFunc').val()) || 0;
        const technical = parseFloat($('#estimateTech').val()) || 0;
        const totalHours = functional + technical;
        const days = totalHours / 8;

        $('#estimateDays').val(days.toFixed(2));
        $('#estimateHours').val(totalHours.toFixed(2));
        $('#estimateDaysText').text(days.toFixed(2));
        $('#estimateHoursText').text(totalHours.toFixed(2));
    });

    $(document).on('submit', '#estimateForm', function(e) {
        e.preventDefault();
        const $form = $(this);
        const taskId = $form.data('task-id');
        const formData = $form.serialize();
        const $saveBtn = $('#estimateSaveBtn');

        if ($saveBtn.prop('disabled')) {
            return;
        }

        const originalBtnText = $saveBtn.text();
        $saveBtn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Updating...'
        );

        $.ajax({
            url: `/tasks/${taskId}/estimates`,
            method: 'POST',
            timeout: 30000,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: formData,
            success: function(response) {
                if (!response.error) {
                    showToast('success', response.message || 'Estimate updated successfully');
                    $('#estimateFunc').val('0');
                    $('#estimateTech').val('0');
                    $('#estimateDays').val('0');
                    $('#estimateHours').val('0');
                    $('#estimateDaysText').text('0.0');
                    $('#estimateHoursText').text('0.00');
                    loadEstimates(taskId, true);
                } else {
                    showToast('error', response.message || 'Failed to update estimate');
                }
            },
            error: function(xhr) {
                if (xhr.statusText === 'timeout') {
                    showToast('error', 'Request timed out. Please try again.');
                } else {
                    showToast('error', (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to update estimate');
                }
            },
            complete: function() {
                $saveBtn.prop('disabled', false).text(originalBtnText || 'Update');
            }
        });
    });

    $(document).on('click', '.approve-estimate-btn', function(e) {
        e.preventDefault();
        const estimateId = $(this).data('id');
        const taskId = $('#estimateForm').data('task-id') || $('#timerBtn').data('task-id');

        $.ajax({
            url: `/tasks/estimates/${estimateId}/approve`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (!response.error) {
                    showToast('success', response.message || 'Estimate approved successfully');
                    loadEstimates(taskId, $('#estimateForm').is(':visible'));
                } else {
                    showToast('error', response.message || 'Failed to approve estimate');
                }
            },
            error: function(xhr) {
                showToast('error', (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to approve estimate');
            }
        });
    });

    function loadEstimates(taskId, canAddEstimate) {
        $.ajax({
            url: `/tasks/${taskId}/estimates`,
            method: 'GET',
            success: function(response) {
                if (!response.error) {
                    renderEstimates(response.data || [], canAddEstimate, taskId);
                } else {
                    $('#estimateList').html('<div class="text-muted">Unable to load estimates</div>');
                    $('#estimateForm').addClass('d-none');
                }
            },
            error: function() {
                $('#estimateList').html('<div class="text-muted">Unable to load estimates</div>');
                $('#estimateForm').addClass('d-none');
            }
        });
    }

    function renderEstimates(estimates, canAddEstimate, taskId) {
        const $form = $('#estimateForm');
        $form.attr('data-task-id', taskId);
        if (canAddEstimate) {
            $form.removeClass('d-none');
        } else {
            $form.addClass('d-none');
        }

        let html = '';
        let topEstimate = '0';
        if (!estimates || estimates.length === 0) {
            html = '<div class="text-muted">No estimates available</div>';
            $('#estimateSaveBtn').text('Add');
            $('#estimateList').html(html);
            $('#taskEstimateTop').text(topEstimate);
            return;
        }

        topEstimate = (estimates[0] && estimates[0].estimate_hours != null) ? estimates[0].estimate_hours : '0';
        $('#taskEstimateTop').text(topEstimate);

        estimates.forEach(function(val) {
            let approveHtml = '';
            if (val.can_approve) {
                approveHtml = `<button class="btn btn-sm btn-primary approve-estimate-btn ms-2" data-id="${val.id}">Approve</button>`;
            } else if (val.estimate_status === 1) {
                approveHtml = `<span class="badge bg-success ms-2">Approved</span>`;
            } else {
                approveHtml = `<span class="badge bg-warning text-dark ms-2">Approval Pending</span>`;
            }

            let lines = '';
            if (val.is_customer) {
                lines += `<div class="text-muted">Total Estimate in Days : ${val.estimate_days ?? ''}</div>`;
                lines += `<div class="text-muted">Total estimate in Hours : ${val.estimate_hours ?? ''}</div>`;
            } else {
                lines += `<div class="text-muted">Estimate Technical : ${val.estimate_tech ?? ''}</div>`;
                lines += `<div class="text-muted">Estimate Functional : ${val.estimate_func ?? ''}</div>`;
                lines += `<div class="text-muted">Total Estimate in Days : ${val.estimate_days ?? ''}</div>`;
                lines += `<div class="text-muted">Total estimate in Hours : ${val.estimate_hours ?? ''}</div>`;
            }

            html += `
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong>${(val.first_name || '')} ${(val.last_name || '')}</strong>
                        <div>
                            <small class="text-muted">${val.created || ''}</small>
                            ${approveHtml}
                        </div>
                    </div>
                    <div class="mt-2">${lines}</div>
                    ${val.approved_by ? `<div class="text-success mt-1">${val.approved_by}</div>` : ''}
                </div>
            `;
        });

        $('#estimateSaveBtn').text('Update');
        $('#estimateList').html(html);
    }

    // Timer functionality
    let timerInterval = null;
    
    function checkTimerStatus(taskId) {
        $.ajax({
            url: `/tasks/${taskId}/timer/status`,
            method: 'GET',
            success: function(response) {
                if (!response.error && response.running) {
                    // Timer is running
                    $('#timerBtnText').text('Stop timer');
                    $('#timerBtn').addClass('timer-running');
                    
                    // Start updating elapsed time
                    startTimerDisplay(response.elapsed_seconds);
                } else {
                    // Timer is not running
                    $('#timerBtnText').text('Start timer');
                    $('#timerBtn').removeClass('timer-running');
                    stopTimerDisplay();
                }
            }
        });
    }
    
    function startTimerDisplay(elapsedSeconds) {
        let seconds = elapsedSeconds;
        
        // Update immediately
        updateTimerDisplay(seconds);
        
        // Update every second
        timerInterval = setInterval(function() {
            seconds++;
            updateTimerDisplay(seconds);
        }, 1000);
    }
    
    function stopTimerDisplay() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }
    
    function updateTimerDisplay(seconds) {
        const hours = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        const timeStr = `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        $('#timerBtnText').text(timeStr);
    }
    
    // Handle timer button click
    $(document).on('click', '#timerBtn', function() {
        const taskId = $(this).data('task-id');
        const isRunning = $(this).hasClass('timer-running');
        
        if (!taskId) {
            showToast('warning', 'No task selected');
            return;
        }
        
        if (isRunning) {
            // Stop timer
            $.ajax({
                url: `/tasks/${taskId}/timer/stop`,
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (!response.error) {
                        $('#timerBtnText').text('Start timer');
                        $('#timerBtn').removeClass('timer-running');
                        stopTimerDisplay();
                        showToast('success', `Timer stopped. Total: ${response.total_hours} hours`);
                        
                        // Reload task detail to update timesheet tab
                        $('.view-task[data-id="' + taskId + '"]').click();
                    } else {
                        showToast('error', response.message || 'Failed to stop timer');
                    }
                },
                error: function() {
                    showToast('error', 'Failed to stop timer');
                }
            });
        } else {
            // Start timer
            $.ajax({
                url: `/tasks/${taskId}/timer/start`,
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (!response.error) {
                        $('#timerBtnText').text('Stop timer');
                        $('#timerBtn').addClass('timer-running');
                        startTimerDisplay(0);
                    } else {
                        showToast('error', response.message || 'Failed to start timer');
                    }
                },
                error: function() {
                    showToast('error', 'Failed to start timer');
                }
            });
        }
    });

    // Clean up timer when modal closes
    $('#taskDetailModal').on('hidden.bs.modal', function() {
        stopTimerDisplay();
    });
    let originalEditStatusOptionsHtml = null;
    let currentEditTaskModalMode = 'edit';

    function applyCloseModeStatusOptions() {
        const $status = $('#edit_status');
        if (!$status.length) return;

        if (originalEditStatusOptionsHtml === null) {
            originalEditStatusOptionsHtml = $status.html();
        }

        const allowedOptions = [];
        $status.find('option').each(function() {
            const label = String($(this).text() || '').trim();
            const normalized = label.toLowerCase().replace(/[\s_-]+/g, '');
            if (normalized === 'closed' || normalized === 'onhold') {
                allowedOptions.push({
                    value: String($(this).val() || ''),
                    label: label
                });
            }
        });

        if (allowedOptions.length) {
            let html = '';
            allowedOptions.forEach(function(opt) {
                html += `<option value="${opt.value}">${opt.label}</option>`;
            });
            $status.html(html);

            const closedOpt = allowedOptions.find(function(opt) {
                return opt.label.toLowerCase().replace(/[\s_-]+/g, '') === 'closed';
            });
            $status.val(closedOpt ? closedOpt.value : allowedOptions[0].value);
            $status.trigger('change.select2');
        }
    }

    function restoreEditStatusOptions() {
        const $status = $('#edit_status');
        if (!$status.length || originalEditStatusOptionsHtml === null) return;

        const currentValue = $status.val();
        $status.html(originalEditStatusOptionsHtml);
        if ($status.find(`option[value="${currentValue}"]`).length) {
            $status.val(currentValue);
        }
        $status.trigger('change.select2');
    }

    function applyConsultantStatusOptions(currentStatusTitle) {
        const $status = $('#edit_status');
        if (!$status.length) return;

        if (originalEditStatusOptionsHtml === null) {
            originalEditStatusOptionsHtml = $status.html();
        }

        const normalizedCurrent = String(currentStatusTitle || '')
            .toLowerCase()
            .replace(/[\s_-]+/g, '');

        const transitions = {
            inprogress: ['undercustomerreview', 'onhold', 'completed'],
            undercustomerreview: ['inprogress', 'onhold', 'completed'],
            onhold: ['inprogress', 'undercustomerreview', 'completed']
        };

        const allowedNext = transitions[normalizedCurrent] || [];
        const selectedBefore = String($status.val() || '');
        const allowedOptions = [];

        $status.find('option').each(function() {
            const label = String($(this).text() || '').trim();
            const value = String($(this).val() || '').trim();
            const normalizedLabel = label.toLowerCase().replace(/[\s_-]+/g, '');
            if (value !== '' && allowedNext.includes(normalizedLabel)) {
                allowedOptions.push({ value: value, label: label });
            }
        });

        if (!allowedOptions.length) {
            // Fallback: keep current status only if no transition is configured.
            const currentOption = $status.find('option').filter(function() {
                const normalizedLabel = String($(this).text() || '').trim().toLowerCase().replace(/[\s_-]+/g, '');
                return normalizedLabel === normalizedCurrent;
            }).first();
            if (currentOption.length) {
                $status.html(`<option value="${currentOption.val()}">${currentOption.text()}</option>`);
                $status.val(String(currentOption.val()));
            }
            $status.trigger('change.select2');
            return;
        }

        let optionsHtml = '';
        allowedOptions.forEach(function(opt) {
            optionsHtml += `<option value="${opt.value}">${opt.label}</option>`;
        });
        $status.html(optionsHtml);

        const selectedStillValid = allowedOptions.some(function(opt) {
            return String(opt.value) === selectedBefore;
        });
        $status.val(selectedStillValid ? selectedBefore : allowedOptions[0].value);
        $status.trigger('change.select2');
    }

    // Toggle edit modal mode: full edit vs close-only view.
    function setEditTaskModalMode(mode) {
        currentEditTaskModalMode = mode;
        const isCloseMode = mode === 'close';
        const isConsultantMode = mode === 'consultant';
        const $modal = $('#editTaskModal');
        $modal.toggleClass('close-mode', isCloseMode);
        $modal.toggleClass('consultant-status-mode', isConsultantMode);
        $('#editTaskModalLabel').text(isCloseMode ? 'Close ticket' : (isConsultantMode ? 'Update ticket status' : 'Edit ticket'));

        if (isCloseMode) {
            applyCloseModeStatusOptions();
        } else {
            restoreEditStatusOptions();
        }
    }

    $('#editTaskModal').on('hidden.bs.modal', function() {
        setEditTaskModalMode('edit');
    });
    // Edit task
    $(document).on('click', '.edit-task', function() {
        if (!canEditTaskAction) return;
        const taskId = $(this).data('id');
        // Load task data and open edit modal
        $.ajax({
            url: `/tasks/${taskId}/edit`,
            method: 'GET',
            success: function(response) {
                if (!response.error) {
                    const statusText = String(response.task?.status_title || response.task?.status || '').trim().toLowerCase();
                    if (isConsultantUser && (statusText === 'closed' || statusText === 'completed')) {
                        showToast('error', 'This Ticket has been Closed/Completed');
                        return;
                    }
                    setEditTaskModalMode(isConsultantUser ? 'consultant' : 'edit');
                    populateEditForm(response.task);
                    if (isConsultantUser) {
                        applyConsultantStatusOptions(response.task?.status_title || response.task?.status || '');
                    }
                    $('#editTaskModal').modal('show');
                }
            },
            error: function(error) {
                showToast('error', 'Error loading task data');
            }
        });
    });

    // Delete task
    $(document).on('click', '.delete-task', function() {
        if (!canDeleteTaskAction) return;
        const taskId = $(this).data('id');
        $('#deleteTaskId').val(taskId);
        $('#deleteTaskModal').modal('show');
    });

    // Close ticket (customer admin flow): open edit popup like existing project.
    $(document).on('click', '.close-ticket-popup', function(e) {
        e.preventDefault();
        if (!canCloseTaskAction) return;

        setEditTaskModalMode('close');
        const taskId = $(this).data('id');
        $.ajax({
            url: `/tasks/${taskId}/edit`,
            method: 'GET',
            success: function(response) {
                if (!response.error) {
                    setEditTaskModalMode('close');
                    populateEditForm(response.task);

                    const $status = $('#edit_status');
                    const closedValue = $status.find('option').filter(function() {
                        return String($(this).text() || '').trim().toLowerCase() === 'closed';
                    }).first().val();

                    if (closedValue) {
                        $status.val(closedValue).trigger('change');
                    }

                    $('#editTaskModal').modal('show');
                }
            },
            error: function() {
                showToast('error', 'Error loading task data');
            }
        });
    });

    function populateEditForm(task) {
        function setSelectValueSmart(selector, value) {
            const $select = $(selector);
            const raw = value == null ? '' : String(value);
            const normalized = raw.toLowerCase().replace(/[\s_-]/g, '');

            // 1) Exact value match
            let $match = $select.find('option').filter(function() {
                return String($(this).val() || '') === raw;
            }).first();

            // 2) Normalized value/text match fallback
            if (!$match.length && normalized) {
                $match = $select.find('option').filter(function() {
                    const ov = String($(this).val() || '').toLowerCase().replace(/[\s_-]/g, '');
                    const ot = String($(this).text() || '').toLowerCase().replace(/[\s_-]/g, '');
                    return ov === normalized || ot === normalized;
                }).first();
            }

            $select.val($match.length ? $match.val() : '');
            $select.trigger('change');
        }

        $('#edit_task_id').val(task.id);
        $('#edit_title').val(task.title);
        $('#edit_description').val(task.description);
        $('#edit_project_id').val(task.project_id);
        setSelectValueSmart('#edit_issue_type_id', task.issue_type);
        $('#edit_project_id').trigger('change');
        setEditServiceValue(task.service, task.project_id);
        // Ensure value stays selected after modal shown hooks run
        $('#editTaskModal').one('shown.bs.modal', function() {
            setEditServiceValue(task.service, task.project_id);
        });
        setSelectValueSmart('#edit_priority_id', task.priority);
        $('#edit_issue_date').val(task.due_date);
        setSelectValueSmart('#edit_status', task.status_title);
        $('#edit_additional_mail').val(task.additional_mail);
        const attachmentName = task.attachment ? String(task.attachment).split('/').pop() : '';
        $('#editAttachmentName').val(attachmentName);
        if (typeof setEditExistingAttachments === 'function') {
            const existingFiles = Array.isArray(task.attachments) ? task.attachments : [];
            const normalized = existingFiles.length
                ? existingFiles
                : (attachmentName ? [{ name: attachmentName }] : []);
            setEditExistingAttachments(normalized);
        }
        
        // Populate Select2 fields for users
        if (task.users) {
            $('#edit_users, #edit_cusers').val(task.users).trigger('change');
        } else {
            $('#edit_users, #edit_cusers').val(null).trigger('change');
        }
    }

    function normalizeServiceValue(value) {
        return String(value || '').trim().toLowerCase();
    }

    function setEditServiceValue(serviceValue, projectId) {
        const $service = $('#edit_service');
        const target = normalizeServiceValue(serviceValue);

        if (!target) {
            $service.val('');
            return;
        }

        let $match = $service.find('option').filter(function() {
            const optionValue = normalizeServiceValue($(this).val());
            const optionText = normalizeServiceValue($(this).text());
            return optionValue === target || optionText === target;
        }).first();

        if (!$match.length) {
            const cleanValue = String(serviceValue).trim();
            $match = $('<option>', {
                value: cleanValue,
                text: cleanValue
            }).attr('data-project', projectId);
            $service.append($match);
        } else {
            $match.attr('data-project', projectId);
        }

        $match.show();
        $service.val($match.val());
    }
</script>
@endpush
