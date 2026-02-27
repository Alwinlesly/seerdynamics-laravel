@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<style>
    /* Filter wrapper styles */
    .rs-sentence {
        margin-bottom: 1.5rem;
    }

    .sel-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .sel-wrapper > div:first-child {
        flex: 0 0 auto;
        min-width: 200px;
    }

    .sel-wrapper .selw-in {
        display: flex;
        gap: 0.75rem;
        flex: 1;
        align-items: center;
    }

    .sel-wrapper .selw-in > div {
        flex: 1;
        min-width: 0;
    }

    .sel-wrapper2 {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        flex-wrap: nowrap;
    }

    .sel-wrapper2 > div {
        flex: 1;
        min-width: 0;
    }

    .sel-wrapper2 > div:first-child {
        flex: 0 0 auto;
        min-width: 120px;
    }

    .sel-wrapper2 .btn-download {
        flex: 0 0 auto;
        width: auto;
    }

    .release-btn {
        background-color: #7d6bb2;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        white-space: nowrap;
    }

    .release-btn:hover {
        background-color: #6a5a9a;
        color: white;
    }

    .btn-download {
        background-color: #7d6bb2;
        color: white;
        border: none;
        padding: 0.5rem 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .btn-download:hover {
        background-color: #6a5a9a;
    }

    /* Pagination styles */
    .pagination {
        gap: 8px;
        justify-content: flex-end;
        margin-top: 1rem;
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

    /* Form select styling */
    .form-select, .form-control {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 0.5rem 0.75rem;
        font-size: 0.95rem;
        width: 100%;
    }

    /* Search wrap */
    .search-wrap {
        position: relative;
        width: 100%;
    }

    .search-icon {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        z-index: 10;
    }

    .search-wrap input {
        padding-right: 35px;
        width: 100%;
    }

    /* Page heading */
    .pg-hd {
        margin: 0;
        font-size: 1.5rem;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="main">
    <!-- Top Header -->
    @include('partials.header')

    <!-- Header -->
    <div class="row m-0">
        @include('partials.sidebar')

        <div class="right-section col-lg-9 px-0 pb-0">
            <div class="px-4 rs-sentence">
                <div class="sel-wrapper w-100">
                    <div>
                        <h1 class="pg-hd"><b>Timesheet Release</b></h1>
                    </div>
                    <div class="selw-in">
                        <div>
                            <select class="form-select" id="consultantFilter">
                                <option value="">Consultants</option>
                                @foreach($consultants as $consultant)
                                <option value="{{ $consultant->id }}">{{ $consultant->first_name }} {{ $consultant->last_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select class="form-select" id="customerFilter">
                                <option value="">Customer</option>
                                @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->company }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <select class="form-select" id="projectTypeFilter">
                                <option value="">Project type</option>
                                @foreach($project_types as $type)
                                <option value="{{ $type->id }}">{{ $type->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <div class="search-create">
                                <div class="search-wrap w-100">
                                    <input type="search" class="form-control form-control-sm py-2" id="searchInput" placeholder="Search" />
                                    <span class="search-icon d-flex">
                                        <svg fill="#9A9A9A" width="15px" height="15px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M21.71,20.29,18,16.61A9,9,0,1,0,16.61,18l3.68,3.68a1,1,0,0,0,1.42,0A1,1,0,0,0,21.71,20.29ZM11,18a7,7,0,1,1,7-7A7,7,0,0,1,11,18Z" />
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sel-wrapper2 w-100">
                    <div>
                        <button class="btn w-100 release-btn" onclick="releaseTimesheets()">Release</button>
                    </div>
                    <div>
                        <select class="form-select" id="projectFilter">
                            <option value="">Project</option>
                            @foreach($projects as $project)
                            <option value="{{ $project->id }}">{{ $project->project_id }} - {{ $project->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <select class="form-select" id="statusFilter">
                            <option value="" selected>Status</option>
                            <option value="0">Pending</option>
                            <option value="1">Released</option>
                        </select>
                    </div>
                    <div>
                        <input type="text" class="form-control" id="fromDateFilter" placeholder="From" readonly>
                    </div>
                    <div>
                        <input type="text" class="form-control" id="toDateFilter" placeholder="To" readonly>
                    </div>
                    <button class="btn btn-sm btn-download" onclick="downloadExcel()">
                        <span>
                            <svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2a1 1 0 0 1 1 1v10.586l2.293-2.293a1 1 0 0 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 1 1 1.414-1.414L11 13.586V3a1 1 0 0 1 1-1zM5 17a1 1 0 0 1 1 1v2h12v-2a1 1 0 1 1 2 0v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a1 1 0 0 1 1-1z" fill="#fff" />
                            </svg>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div>
                <div class="p-4 pb-100">
                    <div class="table-responsive table-x">
                        <form id="releaseForm">
                            <table class="table table-bordered table-hover align-middle tbl-ticket-release">
                                <thead class="table-light">
                                    <tr>
                                        <th class="check-td"><input type="checkbox" class="custom-checkbox" id="checkAll" /></th>
                                        <th scope="col" class="ts-th">Timesheet</th>
                                        <th scope="col" class="ts-th">Project</th>
                                        <th scope="col" class="ts-th">Ticket</th>
                                        <th scope="col" class="has-border">Total Hours</th>
                                        <th scope="col" class="has-border">Total Estimate</th>
                                        <th scope="col" class="has-border">Billable Hours</th>
                                        <th scope="col" class="has-border">Unbillable Hours</th>
                                        <th scope="col" class="has-border">Hours Released So Far</th>
                                        <th scope="col" class="has-border">Released Hours</th>
                                        <th scope="col" class="has-border">Note</th>
                                    </tr>
                                </thead>
                                <tbody id="timesheetTableBody">
                                    <tr>
                                        <td colspan="11" class="text-center">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </form>

                        <div>
                            <div class="d-flex align-items-center gap-2 pagination">
                                <span class="current-total" id="currentPage">1</span>
                                <span class="text-muted">of <span id="totalPages">1</span></span>
                                <div class="d-flex">
                                    <button class="btn btn-light d-flex align-items-center" id="prevPage" disabled>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-left" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0" />
                                        </svg>
                                    </button>
                                    <button class="btn btn-light d-flex align-items-center next-btn" id="nextPage">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16">
                                            <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @include('partials.footer')
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script>
    // Pagination state
    let currentPage = 1;
    let itemsPerPage = 10;
    let totalRecords = 0;
    let searchTimer = null;

    $(document).ready(function() {
        // Make form-select have value class
        document.querySelectorAll(".form-select").forEach((s) => {
            const toggle = () => s.classList.toggle("has-value", !!s.value);
            s.addEventListener("change", toggle);
            toggle();
        });

        // Initialize datepickers
        $('#fromDateFilter, #toDateFilter').datepicker({
            format: 'dd-mm-yyyy',
            autoclose: true
        });

        // Load initial data
        loadTimesheets();

        // Filter change handlers (dropdowns)
        $('.form-select').on('change', function() {
            currentPage = 1;
            loadTimesheets();
        });

        // Search input with debounce
        $('#searchInput').on('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                currentPage = 1;
                loadTimesheets();
            }, 400);
        });

        // Date filter change handlers (bootstrap-datepicker fires 'changeDate')
        $('#fromDateFilter, #toDateFilter').on('changeDate', function() {
            currentPage = 1;
            loadTimesheets();
        });

        // Check all handler
        $('#checkAll').on('change', function() {
            $('.timesheet-checkbox').prop('checked', this.checked);
        });

        // Pagination handlers
        $('#prevPage').on('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadTimesheets();
            }
        });

        $('#nextPage').on('click', function() {
            const totalPages = Math.ceil(totalRecords / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                loadTimesheets();
            }
        });

        // Customer/Project Type change - update projects
        $('#customerFilter, #projectTypeFilter').on('change', function() {
            updateProjectList();
        });
    });

    function loadTimesheets() {
        const offset = (currentPage - 1) * itemsPerPage;

        $.ajax({
            url: '{{ route("timesheets.release.data") }}',
            type: 'GET',
            data: {
                offset: offset,
                limit: itemsPerPage,
                sort: 'id',
                order: 'DESC',
                search: $('#searchInput').val(),
                user_id: $('#consultantFilter').val(),
                customer: $('#customerFilter').val(),
                project: $('#projectFilter').val(),
                status: $('#statusFilter').val(),
                projecttype: $('#projectTypeFilter').val(),
                fromdate: $('#fromDateFilter').val(),
                todate: $('#toDateFilter').val()
            },
            success: function(response) {
                totalRecords = response.total;
                renderTimesheets(response.rows);
                updatePagination();
            },
            error: function(xhr) {
                console.error('Error loading timesheets:', xhr);
                $('#timesheetTableBody').html('<tr><td colspan="11" class="text-center text-danger">Error loading data</td></tr>');
            }
        });
    }

    function renderTimesheets(timesheets) {
        const tbody = $('#timesheetTableBody');
        tbody.empty();

        if (!timesheets || timesheets.length === 0) {
            tbody.append('<tr><td colspan="11" class="text-center">No timesheets found</td></tr>');
            return;
        }

        timesheets.forEach(function(ts) {
            const row = `
            <tr class="ts-row">
                <td class="check-td">
                    <input type="checkbox" class="custom-checkbox timesheet-checkbox" name="releaseids[]" value="${ts.id}" />
                </td>
                <td class="ts-td">
                    <div class="ts-date mb-2">
                        <div><span><b>${ts.timesheet_id}</b></span> <small class="date-sm">[ ${ts.date} ]</small></div>                
                    </div>
                    <div><strong>${ts.consultant}</strong></div>
                    <div class="isApproved c9A9A9A">
                        <p><input type="checkbox" class="custom-checkbox" ${ts.approved_status == 1 ? 'checked' : ''} disabled /> <span>Approved/Not</span></p>
                        <p><input type="checkbox" class="custom-checkbox" ${ts.billable == 1 ? 'checked' : ''} disabled /> <span>Billed/Non-billed</span></p>
                    </div>
                </td>
                <td class="td-project">
                    <div class="proj-col">
                        <h3 class="label-hos"><b>${ts.customer}</b></h3>
                        <div class="pc-bottom">
                            <span class="pc_id"><b>${ts.project_id}</b></span>
                            <span class="pc_sup_hrs">${ts.project}</span>
                        </div>
                    </div>
                </td>
                <td class="td-ticket">
                    <div class="tkt-in">
                        <p class="m-0"><span class="badge-ucr"><b>${ts.ticket_status}</b></span></p>
                        <p class="m-0"><strong>${ts.ticket_id}</strong></p>
                        <p class="c9A9A9A m-0">${ts.ticket_name}</p>
                    </div>
                </td>
                <td class="has-border text-center">${ts.total_hour}</td>
                <td class="has-border text-center">
                    <p>${ts.total_estimate || '-'}</p>
                    <div>
                        <div class="ttl-estimate c9A9A9A text-left">
                            <span><input type="checkbox" class="custom-checkbox" ${ts.estimate_approved == 1 ? 'checked' : ''} disabled></span> 
                            <span>Approved / Not</span>
                        </div>
                    </div>
                </td>
                <td class="has-border text-center">${ts.total_billable_hour}</td>
                <td class="has-border text-center">${ts.total_nonbillable_hour}</td>
                <td class="has-border text-center">${ts.total_hour_released_sofar}</td>
                <td class="has-border text-center">
                    <input class="form-control" type="number" name="release_amt_${ts.id}" value="${ts.released_hour}">
                    <div>
                        <div class="ttl-estimate c9A9A9A text-left">
                            <span><input type="checkbox" class="custom-checkbox" ${ts.release_status == 1 ? 'checked' : ''} disabled></span> 
                            <span>Released / Not</span>
                        </div>
                    </div>
                </td>
                <td class="has-border">${ts.note}</td>
            </tr>
        `;
            tbody.append(row);
        });
    }

    function updatePagination() {
        const totalPages = Math.ceil(totalRecords / itemsPerPage);
        $('#currentPage').text(currentPage);
        $('#totalPages').text(totalPages);

        $('#prevPage').prop('disabled', currentPage === 1);
        $('#nextPage').prop('disabled', currentPage === totalPages || totalPages === 0);
    }

    function releaseTimesheets() {
        const formData = $('#releaseForm').serialize();

        if ($('.timesheet-checkbox:checked').length === 0) {
            showToast('warning', 'Please select at least one timesheet to release');
            return;
        }

        $.ajax({
            url: '{{ route("timesheets.release.save") }}',
            type: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.error) {
                    showToast('error', 'Error: ' + response.message);
                } else {
                    showToast('success', response.message);
                    loadTimesheets();
                }
            },
            error: function(xhr) {
                showToast('error', 'Error releasing timesheets');
                console.error(xhr);
            }
        });
    }

    function updateProjectList() {
        const customerId = $('#customerFilter').val();
        const projectType = $('#projectTypeFilter').val();

        if (!customerId && !projectType) {
            return;
        }

        $.ajax({
            url: '{{ route("timesheets.release.projects") }}',
            type: 'POST',
            data: {
                customerid: customerId,
                projecttype: projectType
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                const projectSelect = $('#projectFilter');
                projectSelect.empty().append('<option value="">Project</option>');

                if (response.projects) {
                    response.projects.forEach(function(project) {
                        projectSelect.append(`<option value="${project.id}">${project.project_id} - ${project.title}</option>`);
                    });
                }
            }
        });
    }

    function downloadExcel() {
        // Implement Excel export functionality
        showToast('info', 'Excel export functionality to be implemented');
    }
</script>
@endpush
