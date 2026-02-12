@extends('layouts.app')

@push('styles')
<style>
/* Pagination Controls Styling */
.pagination {
    gap: 8px;
}

.pagination .btn-light {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 4px 8px;
    background-color: #fff;
    color: #6c757d;
    min-width: 32px;
    height: 32px;
}

.pagination .btn-light:hover:not(:disabled) {
    background-color: #f8f9fa;
    border-color: #adb5bd;
}

.pagination .btn-light:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

#currentPageInput {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 14px;
    height: 32px;
}

#currentPageInput:focus {
    border-color: #7d6bb2;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(125, 107, 178, 0.25);
}

#totalPagesText {
    font-size: 14px;
    color: #6c757d;
}
</style>
@endpush

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

    <div class="row m-0">
        @include('partials.sidebar')

        <div class="right-section col-lg-9 px-0 pb-0">
            <div class="px-4">
                <div class="header mb-0">
                    <h1 class="pg-hd"><b>Timesheet</b></h1>
                    <div class="search-create">
                        <div class="search-wrap">
                            <input type="search" id="searchInput" class="form-control form-control-sm py-2" placeholder="Search" />
                            <span class="search-icon d-flex">
                                <svg fill="#9A9A9A" width="15px" height="15px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21.71,20.29,18,16.61A9,9,0,1,0,16.61,18l3.68,3.68a1,1,0,0,0,1.42,0A1,1,0,0,0,21.71,20.29ZM11,18a7,7,0,1,1,7-7A7,7,0,0,1,11,18Z" />
                                </svg>
                            </span>
                        </div>
                        
                        @if($current_user->inGroup(1))
                        <div>
                            <select class="form-select" id="consultantFilter">
                                <option value="">All Consultants</option>
                                @foreach($consultants as $consultant)
                                    <option value="{{ $consultant->id }}">{{ $consultant->first_name }} {{ $consultant->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        
                        <div>
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="submit">Submitted</option>
                            </select>
                        </div>
                        
                        <button class="btn btn-create" data-bs-toggle="modal" data-bs-target="#createTimesheetModal">
                            <span>
                                <svg fill="#fff" width="20px" height="20px" viewBox="0 0 24 24" id="plus" data-name="Flat Color" xmlns="http://www.w3.org/2000/svg" class="icon flat-color">
                                    <path id="primary" d="M12,20a1,1,0,0,1-1-1V13H5a1,1,0,0,1,0-2h6V5a1,1,0,0,1,2,0v6h6a1,1,0,0,1,0,2H13v6A1,1,0,0,1,12,20Z" style="fill: #fff"></path>
                                </svg>
                            </span> Create
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Table -->
            <div>
                <div class="p-4 pb-100">
                    <div class="table-responsive table-x">
                        <table class="table table-bordered align-middle mb-0 my-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Consultant</th>
                                    <th class="prj-tkt">Work week</th>
                                    <th class="text-center">Billable hours</th>
                                    <th class="text-center">Non-billable hours</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="timesheetTableBody">
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-end align-items-center gap-2 mt-3 pagination">
                            <input type="number" class="form-control text-center" id="currentPageInput" value="1" min="1" style="width: 60px;">
                            <span class="text-muted" id="totalPagesText">of 1</span>
                            
                            <button class="btn btn-light d-flex align-items-center" id="prevPage" disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0"/>
                                </svg>
                            </button>
                        
                            <button class="btn btn-light d-flex align-items-center next-btn" id="nextPage" disabled>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="footer">
                    <div class="d-flex foot-left">
                        <img src="{{ asset('assets/img/logo-360x103.png') }}" alt="">
                        <img src="{{ asset('img/Business-Applications.webp') }}" alt="">
                    </div>
                    <div class="store-apps">
                        <img src="{{ asset('img/microsoft-store.png') }}" alt="Microsoft Store" />
                        <img src="{{ asset('img/playstore.png') }}" alt="Google Play" />
                        <img src="{{ asset('img/appstore.png') }}" alt="App Store" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('timesheets.modals.create')
@include('timesheets.modals.delete')
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize popover
    const profile = document.getElementById('profilePopover');
    new bootstrap.Popover(profile);

    // Sidebar toggle
    const sidebar = document.getElementById("sidebar");
    const hamburger = document.querySelector(".hamburger");

    hamburger.addEventListener("click", function (e) {
        e.stopPropagation();
        sidebar.classList.toggle("active");
        toggleOverlay(sidebar.classList.contains("active"));
    });

    document.addEventListener("click", function (e) {
        const isClickInsideSidebar = sidebar.contains(e.target);
        const isClickOnHamburger = hamburger.contains(e.target);

        if (!isClickInsideSidebar && !isClickOnHamburger) {
            sidebar.classList.remove("active");
            toggleOverlay(false);
        }
    });

    function toggleOverlay(show) {
        let overlay = document.querySelector(".overlay");
        if (show) {
            if (!overlay) {
                overlay = document.createElement("div");
                overlay.classList.add("overlay");
                document.body.appendChild(overlay);
            }
        } else {
            if (overlay) overlay.remove();
        }
    }

    // Load timesheets on page load
    loadTimesheets();
    
    // Search input
    $('#searchInput').on('keyup', function() {
        currentPage = 1; // Reset to first page
        loadTimesheets();
    });
    
    // Consultant filter
    $('#consultantFilter').on('change', function() {
        currentPage = 1; // Reset to first page
        loadTimesheets();
    });
    
    // Status filter
    $('#statusFilter').on('change', function() {
        currentPage = 1; // Reset to first page
        loadTimesheets();
    });
    
    // Delete timesheet click handler
    $(document).on('click', '.delete-timesheet', function() {
        const timesheetId = $(this).data('id');
        const timesheetName = $(this).data('name');
        $('#deleteTimesheetId').val(timesheetId);
        $('#deleteTimesheetName').text(timesheetName);
        $('#deleteTimesheetModal').modal('show');
    });
});

// Pagination state
let currentPage = 1;
let itemsPerPage = 10;
let totalRecords = 0;

function loadTimesheets() {
    const search = $('#searchInput').val() || '';
    const user_id = $('#consultantFilter').val() || '';
    const status = $('#statusFilter').val() || '';
    
    const offset = (currentPage - 1) * itemsPerPage;
    
    console.log('Loading timesheets...');
    console.log('Filters:', { search, user_id, status });
    console.log('Pagination:', { currentPage, itemsPerPage, offset });
    console.log('URL:', '{{ route("timesheets.get") }}');
    
    $.ajax({
        url: '{{ route("timesheets.get") }}',
        method: 'GET',
        data: {
            search: search,
            user_id: user_id,
            status: status,
            offset: offset,
            limit: itemsPerPage
        },
        beforeSend: function() {
            console.log('AJAX request starting...');
            $('#timesheetTableBody').html('<tr><td colspan="7" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>');
        },
        success: function(response) {
            console.log('AJAX Success!');
            console.log('Full response:', response);
            console.log('Response type:', typeof response);
            console.log('Total:', response.total);
            console.log('Rows:', response.rows);
            console.log('Rows length:', response.rows ? response.rows.length : 'undefined');
            
            if (response && response.rows !== undefined) {
                totalRecords = response.total || 0;
                renderTimesheets(response.rows || [], response.total || 0);
                renderPagination();
            } else {
                console.error('Invalid response format:', response);
                $('#timesheetTableBody').html('<tr><td colspan="7" class="text-center text-danger">Error: Invalid response format</td></tr>');
                $('#totalCount').text('0');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error!');
            console.error('Status:', status);
            console.error('Error:', error);
            console.error('XHR:', xhr);
            console.error('Response Text:', xhr.responseText);
            
            const errorMsg = xhr.responseJSON?.message || xhr.statusText || error || 'Unknown error';
            $('#timesheetTableBody').html('<tr><td colspan="7" class="text-center text-danger">Error loading timesheets: ' + errorMsg + '</td></tr>');
            $('#totalCount').text('0');
        },
        complete: function() {
            console.log('AJAX request completed');
        }
    });
}

function renderTimesheets(timesheets, total) {
    console.log('renderTimesheets called with:', timesheets);
    console.log('Timesheets is array?:', Array.isArray(timesheets));
    console.log('Timesheets length:', timesheets ? timesheets.length : 'null/undefined');
    console.log('Total:', total);
    
    const tbody = $('#timesheetTableBody');
    tbody.empty();
    
    if (!timesheets || timesheets.length === 0) {
        console.log('No timesheets found, showing message');
        tbody.append(`
            <tr>
                <td colspan="7" class="text-center">No timesheets found</td>
            </tr>
        `);
        $('#totalCount').text('0');
        return;
    }
    
    console.log('Rendering', timesheets.length, 'timesheets');
    $('#totalCount').text(total || timesheets.length);
    
    timesheets.forEach(function(timesheet, index) {
        console.log('Rendering timesheet', index, ':', timesheet);
        
        const row = `
            <tr>
                <td><span>${timesheet.timesheet_id || 'N/A'}</span></td>
                <td>${timesheet.user || 'N/A'}</td>
                <td>${timesheet.work_week || 'N/A'}</td>
                <td class="text-center">${timesheet.billable || 0}</td>
                <td class="text-center">${timesheet.non_billable || 0}</td>
                <td class="text-center"><span class="${timesheet.status_class || ''}">${timesheet.status || 'N/A'}</span></td>
                <td>
                    <div class="d-flex gap-2 align-items-center justify-content-center">
                        <span class="delete-timesheet" data-id="${timesheet.id}" data-name="${timesheet.timesheet_id || 'Timesheet'}" style="cursor: pointer;">
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
        tbody.append(row);
    });
    
    console.log('Finished rendering all timesheets');
}

function renderPagination() {
    const totalPages = Math.ceil(totalRecords / itemsPerPage);
    
    // Update page input and total pages text
    $('#currentPageInput').val(currentPage);
    $('#currentPageInput').attr('max', totalPages);
    $('#totalPagesText').text(`of ${totalPages}`);
    
    // Enable/disable Previous button
    if (currentPage === 1) {
        $('#prevPage').prop('disabled', true);
    } else {
        $('#prevPage').prop('disabled', false);
    }
    
    // Enable/disable Next button
    if (currentPage === totalPages || totalPages === 0) {
        $('#nextPage').prop('disabled', true);
    } else {
        $('#nextPage').prop('disabled', false);
    }
}

// Pagination event handlers
$(document).ready(function() {
    // Page input change handler
    $('#currentPageInput').on('change', function() {
        const totalPages = Math.ceil(totalRecords / itemsPerPage);
        let newPage = parseInt($(this).val());
        
        // Validate page number
        if (isNaN(newPage) || newPage < 1) {
            newPage = 1;
        } else if (newPage > totalPages) {
            newPage = totalPages;
        }
        
        if (newPage !== currentPage) {
            currentPage = newPage;
            loadTimesheets();
        } else {
            // Reset input to current page if invalid
            $(this).val(currentPage);
        }
    });
    
    // Previous page button
    $('#prevPage').on('click', function() {
        if (currentPage > 1) {
            currentPage--;
            loadTimesheets();
        }
    });
    
    // Next page button
    $('#nextPage').on('click', function() {
        const totalPages = Math.ceil(totalRecords / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            loadTimesheets();
        }
    });
});
</script>
@endpush
