@extends('layouts.app')

@section('content')
<div class="main">
    <!-- Top Header -->
    @include('partials.header')

    <div class="row m-0">
        @include('partials.sidebar')

        <div class="right-section col-lg-9 px-0 pb-0">
            <div class="px-4">
                <div class="header">
                    <h1 class="pg-hd"><b>Projects</b></h1>
                    <div class="search-create">
                        <div class="search-wrap">
                            <input type="search" id="searchInput" class="form-control form-control-sm py-2"
                                placeholder="Search" />
                            <span class="search-icon d-flex">
                                <svg fill="#9A9A9A" width="15px" height="15px" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path
                                        d="M21.71,20.29,18,16.61A9,9,0,1,0,16.61,18l3.68,3.68a1,1,0,0,0,1.42,0A1,1,0,0,0,21.71,20.29ZM11,18a7,7,0,1,1,7-7A7,7,0,0,1,11,18Z" />
                                </svg>
                            </span>
                        </div>

                        <button class="btn btn-create" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                            <span>
                                <svg fill="#fff" width="20px" height="20px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12,20a1,1,0,0,1-1-1V13H5a1,1,0,0,1,0-2h6V5a1,1,0,0,1,2,0v6h6a1,1,0,0,1,0,2H13v6A1,1,0,0,1,12,20Z"></path>
                                </svg>
                            </span> Create
                        </button>
                    </div>
                </div>

                <div class="sel-wrapper">
                    <select class="form-select" id="projectFilter">
                        <option value="">Project</option>
                        @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->project_id }} - {{ $project->title }}</option>
                        @endforeach
                    </select>
                    <select class="form-select" id="consultantFilter">
                        <option value="">Consultant</option>
                        @foreach($consultants as $consultant)
                        <option value="{{ $consultant->id }}">{{ $consultant->first_name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select" id="customerFilter">
                        <option value="">Customer</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->company ?? $customer->first_name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select" id="statusFilter">
                        <option value="">Status</option>
                        @foreach($project_statuses as $status)
                        <option value="{{ $status->title }}">{{ $status->title }}</option>
                        @endforeach
                    </select>
                    <select class="form-select" id="sortFilter">
                        <option value="id">Sort</option>
                        <option value="created">Latest</option>
                        <option value="title">Title</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="p-4">
                <div class="table-responsive table-x">
                    <table class="table table-bordered align-middle mb-0 my-table-project" id="projectsTable">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Customer</th>
                                <th>
                                    <div class="text-center">
                                        <span>Ticket</span>
                                        <span class="th-complete">(completed)</span>
                                    </div>
                                </th>
                                <th>From</th>
                                <th>To</th>
                                <th>Total hours purchased</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="projectsTableBody">
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>

                    <div class="d-flex align-items-center gap-2 pagination mt-3">
                        <span class="current-total" id="currentPage">1</span>
                        <span class="text-muted">of <span id="totalPages">0</span></span>
                        <div class="d-flex">
                            <button class="btn btn-light d-flex align-items-center" id="prevBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0" />
                                </svg>
                            </button>
                            <button class="btn btn-light d-flex align-items-center next-btn" id="nextBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708" />
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

<!-- Create Project Modal -->
@include('projects.modals.create')

<!-- Edit Project Modal -->
@include('projects.modals.edit')

<!-- Delete Confirmation Modal -->
@include('projects.modals.delete')

@endsection

@push('scripts')
<script>
let currentPage = 1;
const limit = 20;

$(document).ready(function() {
    loadProjects();
    
    // Search
    $('#searchInput').on('keyup', debounce(function() {
        currentPage = 1;
        loadProjects();
    }, 500));
    
    // Filters
    $('#projectFilter, #consultantFilter, #customerFilter, #statusFilter, #sortFilter').on('change', function() {
        currentPage = 1;
        loadProjects();
    });
    
    // Pagination
    $('#prevBtn').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            loadProjects();
        }
    });
    
    $('#nextBtn').on('click', function() {
        currentPage++;
        loadProjects();
    });
});

function loadProjects() {
    const search = $('#searchInput').val();
    const project = $('#projectFilter').val();
    const consultant = $('#consultantFilter').val();
    const customer = $('#customerFilter').val();
    const status = $('#statusFilter').val();
    const sort = $('#sortFilter').val();
    
    console.log('Loading projects...', {
        url: '{{ route("projects.list") }}',
        search, project, consultant, customer, status, sort
    });
    
    $.ajax({
        url: '{{ route("projects.list") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            search: search,
            project: project,
            consultant: consultant,
            customer: customer,
            status: status,
            sort: sort,
            limit: limit,
            offset: (currentPage - 1) * limit
        },
        success: function(response) {
            console.log('Projects loaded:', response);
            renderProjects(response.rows);
            updatePagination(response.total);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            $('#projectsTableBody').html('<tr><td colspan="8" class="text-center text-danger">Error loading projects: ' + error + '</td></tr>');
        }
    });
}

function renderProjects(projects) {
    let html = '';
    
    projects.forEach(function(project) {
        const status = project.status || 'Open';
        const statusClass = status.toLowerCase().replace(' ', '-');
        html += `
            <tr>
                <td><a href="/projects/${project.id}" style="color: #7d6bb2; text-decoration: none;">${project.project_id} ${project.title}</a></td>
                <td>${project.customer}</td>
                <td class="text-center">${project.tickets}</td>
                <td>${project.from}</td>
                <td>${project.to}</td>
                <td class="text-center">${project.total_hours}</td>
                <td><span class="status ${statusClass}">${status}</span></td>
                <td>
                    <div class="d-flex gap-2 align-items-center justify-content-center">
                        <span class="edit-project" data-id="${project.id}" style="cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7d6bb2" stroke-width="2">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                            </svg>
                        </span>
                        <span class="delete-project" data-id="${project.id}" style="cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 24 24" fill="none" stroke="#7d6bb2" stroke-width="2">
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
    
    $('#projectsTableBody').html(html || '<tr><td colspan="8" class="text-center">No projects found</td></tr>');
}

function updatePagination(total) {
    const totalPages = Math.ceil(total / limit);
    $('#currentPage').text(currentPage);
    $('#totalPages').text(totalPages);
    
    $('#prevBtn').prop('disabled', currentPage === 1);
    $('#nextBtn').prop('disabled', currentPage >= totalPages);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Edit project
$(document).on('click', '.edit-project', function() {
    const projectId = $(this).data('id');
    
    // Fetch project data
    $.ajax({
        url: '/projects/' + projectId + '/edit',
        method: 'GET',
        success: function(data) {
            if (!data.error) {
                const project = data.project;
                
                // Populate form fields
                $('#editProjectId').val(project.id);
                $('#edit_title').val(project.title);
                $('#edit_description').val(project.description);
                $('#edit_services_offered').val(project.services_offered);
                $('#edit_starting_date').val(project.starting_date);
                $('#edit_ending_date').val(project.ending_date);
                $('#edit_actual_starting_date').val(project.actual_starting_date || '');
                $('#edit_actual_ending_date').val(project.actual_ending_date || '');
                $('#edit_project_value').val(project.project_value || '');
                $('#edit_project_currency').val(project.project_currency || '');
                $('#edit_total_hours').val(project.total_hours || '');
                $('#edit_status').val(project.status_title);
                $('#edit_project_type').val(project.project_type || '');
                $('#edit_client_id').val(project.client_id);
                
                // Handle assigned consultants
                if (project.assigned_users && project.assigned_users.length > 0) {
                    $('#edit_assigned_consultants').val(project.assigned_users.join(','));
                }
                
                // Checkboxes
                $('#editDefaultProject').prop('checked', project.is_default == 1);
                $('#editVisibleToCustomer').prop('checked', project.is_visible_to_customer == 0);
                
                // Contract file display
                if (project.contract_copy) {
                    $('#editContractFileName').val(project.contract_copy.split('/').pop());
                }
                
                // Show modal
                $('#editProjectModal').modal('show');
            } else {
                showToast('error', data.message);
            }
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            showToast('error', 'Error loading project data');
        }
    });
});

// Delete project
$(document).on('click', '.delete-project', function() {
    const projectId = $(this).data('id');
    $('#deleteProjectId').val(projectId);
    $('#deleteProjectModal').modal('show');
});
</script>
@endpush
