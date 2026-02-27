@extends('layouts.app')

@section('content')
<div class="main">
    <!-- Top Header -->
    <div class="top-header">
        <div class="d-flex align-items-center gap-3">
            <div class="hamburger is-lg">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </div>
            <div class="logo">
                <img src="{{ asset('assets/img/logo-360x103.png') }}" alt="">
            </div>
        </div>

        <div class="top-right">
            <div class="profile-container" id="profilePopover" data-bs-toggle="popover" data-bs-html="true"
                data-bs-placement="bottom">
                <img src="{{ asset('assets/img/mrs1.webp') }}" alt="User Profile">
                <div class="profile-info">
                    <div class="name">{{ $current_user->first_name }} {{ $current_user->last_name }}</div>
                    <div class="email">{{ $current_user->email }}</div>
                </div>
                <div class="dropdown-icon">▼</div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="row m-0">
        @include('partials.sidebar')

        <div class="right-section col-lg-9 px-0 pb-0">
            <div class="px-4">
                <div class="header">
                    <h1 class="pg-hd"><b>Consultants</b></h1>
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

                        @if(auth()->user()->inGroup(1))
                        <button class="btn btn-create" data-bs-toggle="modal" data-bs-target="#createModal">
                            <span>
                                <svg fill="#fff" width="20px" height="20px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12,20a1,1,0,0,1-1-1V13H5a1,1,0,0,1,0-2h6V5a1,1,0,0,1,2,0v6h6a1,1,0,0,1,0,2H13v6A1,1,0,0,1,12,20Z" style="fill: #fff"></path>
                                </svg>
                            </span> Create
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div>
                <div class="p-4 pt-0">
                    <div class="table-responsive table-x table-consultant">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="text-muted small fw-medium">Consultants</th>
                                        <th scope="col" class="text-muted small fw-medium">Email</th>
                                        <th scope="col" class="text-muted small fw-medium">Mobile</th>
                                        <th scope="col" class="text-muted small fw-medium">Role</th>
                                        <th scope="col" class="text-muted small fw-medium">Project</th>
                                        <th scope="col" class="text-muted small fw-medium">Ticket</th>
                                        <th scope="col" class="text-muted small fw-medium">Status</th>
                                        @if(auth()->user()->inGroup(1))
                                        <th scope="col" class="text-muted small fw-medium">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody id="consultantsTableBody">
                                    @if(!empty($system_users))
                                    @foreach($system_users as $suser)
                                    <tr class="consultant-row">
                                        <td>
                                            <div class="d-flex align-items-center gap-2 con_name">
                                                @if(!empty($suser['profile']))
                                                <img src="{{ $suser['profile'] }}" alt="{{ $suser['first_name'] }}" class="rounded-circle profile-img">
                                                @else
                                                <div class="rounded-circle profile-img d-flex align-items-center justify-content-center" style="width:50px;height:50px;background:#513998;color:#fff;font-weight:600;">
                                                    {{ $suser['short_name'] }}
                                                </div>
                                                @endif
                                                <span class="fw-medium">{{ $suser['first_name'] }} {{ $suser['last_name'] }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $suser['email'] }}</td>
                                        <td>{{ $suser['phone'] }}</td>
                                        <td>{{ $suser['role'] }}</td>
                                        <td>{{ $suser['projects_count'] }}</td>
                                        <td>{{ $suser['tasks_count'] }}</td>
                                        <td>
                                            @if($suser['active'] == 1)
                                            <span class="status-active">Active</span>
                                            @else
                                            <span class="status-incative">Inactive</span>
                                            @endif
                                        </td>
                                        @if(auth()->user()->inGroup(1))
                                        <td>
                                            <div class="d-flex gap-2 align-items-center justify-content-center">
                                                <span style="cursor:pointer;" onclick="editConsultant({{ $suser['id'] }})">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#7d6bb2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"></path>
                                                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                    </svg>
                                                </span>
                                                <span style="cursor:pointer;" onclick="deleteConsultant({{ $suser['id'] }})">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 24 24" fill="none" stroke="#7d6bb2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                        <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                        </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                    @else
                                    <tr>
                                        <td colspan="8" class="text-center">No consultants found</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 pagination">
                                <span class="current-total" id="currentPage">1</span>
                                <span class="text-muted">of <span id="totalPages">1</span></span>
                                <div class="d-flex">
                                    <button class="btn btn-light d-flex align-items-center" id="prevBtn" disabled>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
                                        </svg>
                                    </button>
                                    <button class="btn btn-light d-flex align-items-center next-btn" id="nextBtn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('partials.footer')
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
                <h5 class="modal-title" id="createModalLabel">Create new consultant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body create-ticket-body">
                <form id="createForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="create_first_name" class="form-label">First name <span class="req">*</span></label>
                            <input type="text" class="form-control" id="create_first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="create_last_name" class="form-label">Last name</label>
                            <input type="text" class="form-control" id="create_last_name" name="last_name">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="create_email" class="form-label">Email <span class="req">*</span></label>
                            <input type="email" class="form-control" id="create_email" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="create_phone" class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="create_phone" name="phone">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="create_password" class="form-label">Password <span class="req">*</span></label>
                            <input type="password" class="form-control" id="create_password" name="password" required>
                        </div>
                        <div class="col-md-6">
                            <label for="create_password_confirmation" class="form-label">Confirm Password <span class="req">*</span></label>
                            <input type="password" class="form-control" id="create_password_confirmation" name="password_confirmation" required>
                        </div>
                    </div>
                    <div class="row mb-3 align-items-center">
                        <div class="col-lg-12 col-pm justify-content-end">
                            <div class="add-mail-right">
                                <button type="submit" class="btn btn-primary">Create</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-otd modal-header d-flex justify-content-between align-items-center gap-4">
                <h5 class="modal-title" id="editModalLabel">Edit consultant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body create-ticket-body">
                <form id="editForm">
                    <input type="hidden" id="edit_id" name="update_id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_first_name" class="form-label">First name <span class="req">*</span></label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_last_name" class="form-label">Last name</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_phone" class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="edit_phone" name="phone">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_password" class="form-label">Password <small class="text-muted">(leave empty for no change)</small></label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                    </div>
                    <div class="row mb-3 align-items-center">
                        <div class="col-lg-12 col-pm justify-content-end">
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="deact-btn" id="toggleStatusBtn" onclick="toggleStatus()">Deactivate</button>
                                <button type="submit" class="update-btn">Update</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this consultant?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="delete-btn" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    var currentEditId = null;
    var currentEditActive = null;
    var deleteId = null;
    var currentPage = 1;
    var rowsPerPage = 10;

    // Pagination
    function getVisibleRows() {
        var value = $('#searchInput').val().toLowerCase();
        return $('.consultant-row').filter(function() {
            return $(this).text().toLowerCase().indexOf(value) > -1;
        });
    }

    function updatePagination() {
        var visibleRows = getVisibleRows();
        var totalRows = visibleRows.length;
        var totalPages = Math.max(1, Math.ceil(totalRows / rowsPerPage));

        if (currentPage > totalPages) currentPage = totalPages;

        // Hide all rows first
        $('.consultant-row').hide();

        // Show only rows for current page
        var start = (currentPage - 1) * rowsPerPage;
        var end = start + rowsPerPage;
        visibleRows.slice(start, end).show();

        // Update pagination display
        $('#currentPage').text(currentPage);
        $('#totalPages').text(totalPages);
        $('#prevBtn').prop('disabled', currentPage <= 1);
        $('#nextBtn').prop('disabled', currentPage >= totalPages);
    }

    $('#prevBtn').on('click', function() {
        if (currentPage > 1) { currentPage--; updatePagination(); }
    });

    $('#nextBtn').on('click', function() {
        var totalPages = Math.max(1, Math.ceil(getVisibleRows().length / rowsPerPage));
        if (currentPage < totalPages) { currentPage++; updatePagination(); }
    });

    // Search with pagination reset
    $('#searchInput').on('keyup', function() {
        currentPage = 1;
        updatePagination();
    });

    // Initialize pagination on page load
    $(document).ready(function() {
        updatePagination();
    });

    // Create form
    $('#createForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("consultants.store") }}',
            type: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (!response.error) {
                    $('#createModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                var errors = xhr.responseJSON;
                if (errors && errors.errors) {
                    var msg = '';
                    $.each(errors.errors, function(key, val) { msg += val[0] + '\n'; });
                    alert(msg);
                } else {
                    alert('Error creating consultant');
                }
            }
        });
    });

    // Edit consultant
    function editConsultant(id) {
        $.ajax({
            url: '{{ url("consultants") }}/' + id,
            type: 'GET',
            success: function(response) {
                if (!response.error) {
                    var data = response.data;
                    currentEditId = data.id;
                    currentEditActive = data.active;
                    $('#edit_id').val(data.id);
                    $('#edit_first_name').val(data.first_name);
                    $('#edit_last_name').val(data.last_name);
                    $('#edit_phone').val(data.phone);
                    $('#edit_password').val('');
                    
                    if (data.active == 1) {
                        $('#toggleStatusBtn').text('Deactivate').removeClass('update-btn').addClass('deact-btn');
                    } else {
                        $('#toggleStatusBtn').text('Activate').removeClass('deact-btn').addClass('update-btn');
                    }
                    
                    $('#editModal').modal('show');
                } else {
                    alert(response.message);
                }
            }
        });
    }

    // Edit form submit
    $('#editForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '{{ route("consultants.update") }}',
            type: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (!response.error) {
                    $('#editModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                alert('Error updating consultant');
            }
        });
    });

    // Toggle status (activate/deactivate)
    function toggleStatus() {
        if (!currentEditId) return;
        
        var url = currentEditActive == 1 
            ? '{{ url("consultants/deactivate") }}/' + currentEditId
            : '{{ url("consultants/activate") }}/' + currentEditId;
        
        $.ajax({
            url: url,
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (!response.error) {
                    $('#editModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message);
                }
            }
        });
    }

    // Delete consultant
    function deleteConsultant(id) {
        deleteId = id;
        $('#deleteModal').modal('show');
    }

    $('#confirmDeleteBtn').on('click', function() {
        if (!deleteId) return;
        $.ajax({
            url: '{{ url("consultants") }}/' + deleteId,
            type: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (!response.error) {
                    $('#deleteModal').modal('hide');
                    location.reload();
                } else {
                    alert(response.message);
                }
            }
        });
    });
</script>
@endpush
