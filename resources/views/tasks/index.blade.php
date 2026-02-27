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
                    </div>
                </div>

                <div class="sel-wrapper">
                    <select class="form-select" id="customerFilter">
                        <option value="">Customer</option>
                        @if(isset($customers))
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->company ?? $customer->first_name }}</option>
                            @endforeach
                        @endif
                    </select>

                    <select class="form-select" id="projectFilter">
                        <option value="">Project</option>
                        @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->project_id }} - {{ $project->title }}</option>
                        @endforeach
                    </select>

                    <select class="form-select" id="statusFilter">
                        <option value="">Status</option>
                        @foreach($task_statuses as $status)
                            <option value="{{ $status->title }}">{{ $status->title }}</option>
                        @endforeach
                    </select>

                    <select class="form-select" id="priorityFilter">
                        <option value="">Priority</option>
                        @foreach($priorities as $priority)
                            <option value="{{ $priority->id }}">{{ $priority->title }}</option>
                        @endforeach
                    </select>

                    <select class="form-select" id="sortFilter">
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
                                <!-- Tasks will be loaded here via AJAX -->
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

@push('scripts')
<script>
    let currentPage = 1;
    let totalRecords = 0;
    const limit = 20;

    $(document).ready(function() {
        // Check if project filter is passed in URL
        const urlParams = new URLSearchParams(window.location.search);
        const projectId = urlParams.get('project');
        if (projectId) {
            $('#projectFilter').val(projectId);
        }
        
        loadTasks();

        // Search
        $('#searchInput').on('keyup', function() {
            currentPage = 1;
            loadTasks();
        });

        // Filters
        $('#customerFilter, #projectFilter, #statusFilter, #priorityFilter, #sortFilter').on('change', function() {
            currentPage = 1;
            loadTasks();
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
            const project = $('#projectFilter').val();
            const status = $('#statusFilter').val();
            const priority = $('#priorityFilter').val();
            const sort = $('#sortFilter').val();

            // Build query string
            let params = new URLSearchParams();
            if (search) params.append('search', search);
            if (customer) params.append('customer', customer);
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
        const project = $('#projectFilter').val();
        const status = $('#statusFilter').val();
        const priority = $('#priorityFilter').val();
        const sort = $('#sortFilter').val();
        const offset = (currentPage - 1) * limit;

        $.ajax({
            url: '/tasks/list',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            data: {
                search: search,
                customer: customer,
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
                const priorityClass = task.priority_class;

                html += `
                    <tr>
                        <td>${task.ticket_id}</td>
                        <td><a href="#" class="view-task" data-id="${task.id}" style="color: inherit; text-decoration: none; cursor: pointer;">${task.title}</a></td>
                        <td>${task.project}</td>
                        <td>${task.customer}</td>
                        <td class="text-center">${task.estimate}</td>
                        <td><span class="${priorityClass}-pr">${task.priority}</span></td>
                        <td>${task.created_by}</td>
                        <td>${task.created_date}</td>
                        <td><span class="status ${statusClass}">${task.status}</span></td>
                        <td>
                            <div class="d-flex gap-2 align-items-center justify-content-center">
                                <span class="edit-task" data-id="${task.id}" style="cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7d6bb2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </span>
                                <span class="delete-task" data-id="${task.id}" style="cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 24 24" fill="none" stroke="#7d6bb2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <line x1="10" y1="11" x2="10" y2="17"/>
                                        <line x1="14" y1="11" x2="14" y2="17"/>
                                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                    </svg>
                                </span>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }

        $('#tasksTableBody').html(html);
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
                    // Check timer status
                    checkTimerStatus(taskId);
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

    // Handle file input display in modal
    $(document).on('change', '#commentFileInput', function() {
        const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
        $('#fileNameDisplay').val(fileName);
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
                    // Reload task detail to show new comment
                    $('.view-task[data-id="' + taskId + '"]').click();
                    alert('Comment added successfully');
                }
            },
            error: function() {
                alert('Failed to add comment');
            }
        });
    });

    // Handle estimate calculations
    $(document).on('input', '#functionalEstimate, #technicalEstimate', function() {
        const functional = parseFloat($('#functionalEstimate').val()) || 0;
        const technical = parseFloat($('#technicalEstimate').val()) || 0;
        const totalHours = functional + technical;
        const days = (totalHours / 8).toFixed(1);
        const hours = Math.floor(totalHours);
        const minutes = Math.round((totalHours - hours) * 60);
        
        $('#daysEstimate').text(days);
        $('#hoursEstimate').text(`${hours}:${minutes.toString().padStart(2, '0')}`);
    });

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
            alert('No task selected');
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
                        alert(`Timer stopped. Total: ${response.total_hours} hours`);
                        
                        // Reload task detail to update timesheet tab
                        $('.view-task[data-id="' + taskId + '"]').click();
                    } else {
                        alert(response.message || 'Failed to stop timer');
                    }
                },
                error: function() {
                    alert('Failed to stop timer');
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
                        alert(response.message || 'Failed to start timer');
                    }
                },
                error: function() {
                    alert('Failed to start timer');
                }
            });
        }
    });

    // Clean up timer when modal closes
    $('#taskDetailModal').on('hidden.bs.modal', function() {
        stopTimerDisplay();
    });

    // Edit task
    $(document).on('click', '.edit-task', function() {
        const taskId = $(this).data('id');
        // Load task data and open edit modal
        $.ajax({
            url: `/tasks/${taskId}/edit`,
            method: 'GET',
            success: function(response) {
                if (!response.error) {
                    populateEditForm(response.task);
                    $('#editTaskModal').modal('show');
                }
            },
            error: function(error) {
                alert('Error loading task data');
            }
        });
    });

    // Delete task
    $(document).on('click', '.delete-task', function() {
        const taskId = $(this).data('id');
        $('#deleteTaskId').val(taskId);
        $('#deleteTaskModal').modal('show');
    });

    function populateEditForm(task) {
        $('#edit_task_id').val(task.id);
        $('#edit_title').val(task.title);
        $('#edit_description').val(task.description);
        $('#edit_project_id').val(task.project_id);
        $('#edit_issue_type_id').val(task.issue_type_id);
        $('#edit_service').val(task.service);
        $('#edit_priority_id').val(task.priority_id);
        $('#edit_issue_date').val(task.issue_date);
        $('#edit_status').val(task.status_title);
        $('#edit_additional_mail').val(task.additional_mail);
    }
</script>
@endpush
