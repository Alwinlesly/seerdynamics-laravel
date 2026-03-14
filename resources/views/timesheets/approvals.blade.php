@extends('layouts.app')

@section('content')
<div class="main">
    @include('partials.header')

    <div class="row m-0">
        @include('partials.sidebar')

        <div class="right-section col-lg-9 px-0 pb-0">
            <div class="px-4">
                <div class="header">
                    <h1 class="pg-hd"><b>Timesheet Approval</b></h1>
                    <div class="search-create" style="min-width: 320px;">
                        <div class="search-wrap w-100">
                            <input type="search" id="searchInput" class="form-control form-control-sm py-2" placeholder="Search" />
                            <span class="search-icon d-flex">
                                <svg fill="#9A9A9A" width="15px" height="15px" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21.71,20.29,18,16.61A9,9,0,1,0,16.61,18l3.68,3.68a1,1,0,0,0,1.42,0A1,1,0,0,0,21.71,20.29ZM11,18a7,7,0,1,1,7-7A7,7,0,0,1,11,18Z" />
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="sel-wrapper">
                    <select class="form-select approve-filter" id="consultantFilter">
                        <option value="">Consultant</option>
                        @foreach($consultants as $consultant)
                        <option value="{{ $consultant->id }}">{{ $consultant->first_name }} {{ $consultant->last_name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select approve-filter" id="customerFilter">
                        <option value="">Customer</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->company }}</option>
                        @endforeach
                    </select>
                    <select class="form-select approve-filter" id="projectFilter">
                        <option value="">Project</option>
                        @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->project_id }} - {{ $project->title }}</option>
                        @endforeach
                    </select>
                    <select class="form-select approve-filter" id="statusFilter">
                        <option value="">Status</option>
                        <option value="0">Pending</option>
                        <option value="1">Approved</option>
                        <option value="2">Rejected</option>
                    </select>
                </div>
            </div>

            <div class="p-4">
                <div class="table-responsive table-x">
                    <table class="table table-bordered align-middle mb-0 my-table-project">
                        <thead>
                            <tr>
                                <th>Timesheet ID</th>
                                <th>Consultant</th>
                                <th>Customer</th>
                                <th>Billable</th>
                                <th>Project</th>
                                <th>Task</th>
                                <th>Work Week</th>
                                <th>Total Hour</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="approvalTableBody">
                            <tr><td colspan="10" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>

                    <div class="d-flex align-items-center gap-2 pagination mt-3">
                        <span class="current-total" id="currentPage">1</span>
                        <span class="text-muted">of <span id="totalPages">1</span></span>
                        <div class="d-flex">
                            <button class="btn btn-light d-flex align-items-center" id="prevBtn" disabled>
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

<div class="modal fade" id="hoursDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Day Wise Hours</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Hour</th>
                                <th>Note</th>
                            </tr>
                        </thead>
                        <tbody id="hoursDetailBody">
                            <tr><td colspan="3" class="text-center">No data</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentPage = 1;
const limit = 10;
let totalRows = 0;

$(document).ready(function () {
    loadApprovals();

    let timer;
    $('#searchInput').on('keyup', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            currentPage = 1;
            loadApprovals();
        }, 350);
    });

    $('.approve-filter').on('change', function () {
        currentPage = 1;
        loadApprovals();
    });

    $('#prevBtn').on('click', function () {
        if (currentPage > 1) {
            currentPage--;
            loadApprovals();
        }
    });

    $('#nextBtn').on('click', function () {
        const totalPages = Math.ceil(totalRows / limit);
        if (currentPage < totalPages) {
            currentPage++;
            loadApprovals();
        }
    });
});

function loadApprovals() {
    $.ajax({
        url: '{{ route("timesheets.approvals.list") }}',
        method: 'GET',
        data: {
            offset: (currentPage - 1) * limit,
            limit: limit,
            search: $('#searchInput').val(),
            user_id: $('#consultantFilter').val(),
            customer: $('#customerFilter').val(),
            project: $('#projectFilter').val(),
            status: $('#statusFilter').val()
        },
        success: function (response) {
            totalRows = response.total || 0;
            renderRows(response.rows || []);
            updatePagination();
        },
        error: function (xhr) {
            $('#approvalTableBody').html('<tr><td colspan="10" class="text-center text-danger">Failed to load approvals.</td></tr>');
            showToast('error', xhr.responseJSON?.message || 'Failed to load approvals.');
        }
    });
}

function renderRows(rows) {
    if (!rows.length) {
        $('#approvalTableBody').html('<tr><td colspan="10" class="text-center">No records found</td></tr>');
        return;
    }

    let html = '';
    rows.forEach(function (row) {
        let statusHtml = '<span class="badge bg-info">Pending</span>';
        if (row.approved_status === 1) {
            statusHtml = '<span class="badge bg-success">Approved</span>';
        } else if (row.approved_status === 2) {
            statusHtml = '<span class="badge bg-danger">Rejected</span>';
        }

        const billableHtml = row.billable === 1
            ? '<input type="checkbox" checked disabled>'
            : '<input type="checkbox" disabled>';

        let actions = '';
        if (row.approved_status === 0 || row.approved_status === 2) {
            actions += `<button class="btn btn-sm btn-success me-1" onclick="approveTimesheet(${row.time_pjt_id})" title="Approve"><i class="bi bi-check-lg"></i></button>`;
        }
        actions += `<button class="btn btn-sm btn-secondary me-1" onclick="showDetails(${row.time_pjt_id})" title="View"><i class="bi bi-eye"></i></button>`;
        if (row.approved_status === 0 || row.approved_status === 1) {
            actions += `<button class="btn btn-sm btn-danger" onclick="rejectTimesheet(${row.time_pjt_id})" title="Reject"><i class="bi bi-x-lg"></i></button>`;
        }

        html += `
            <tr>
                <td>${row.timesheet_id}</td>
                <td>${escapeHtml(row.consultant || '')}</td>
                <td>${escapeHtml(row.customer || '')}</td>
                <td>${billableHtml}</td>
                <td>${escapeHtml(row.project || '')}</td>
                <td>${escapeHtml(row.task || '')}</td>
                <td>${escapeHtml(row.work_week || '')}</td>
                <td>${row.totalhour}</td>
                <td>${statusHtml}</td>
                <td class="text-center">${actions}</td>
            </tr>
        `;
    });

    $('#approvalTableBody').html(html);
}

function updatePagination() {
    const totalPages = Math.max(1, Math.ceil(totalRows / limit));
    $('#currentPage').text(currentPage);
    $('#totalPages').text(totalPages);
    $('#prevBtn').prop('disabled', currentPage <= 1);
    $('#nextBtn').prop('disabled', currentPage >= totalPages);
}

function approveTimesheet(id) {
    $.ajax({
        url: `/projects/timesheetapprovals/${id}/approve`,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function (response) {
            showToast('success', response.message || 'Approved successfully.');
            loadApprovals();
        },
        error: function (xhr) {
            showToast('error', xhr.responseJSON?.message || 'Failed to approve.');
        }
    });
}

function rejectTimesheet(id) {
    $.ajax({
        url: `/projects/timesheetapprovals/${id}/reject`,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function (response) {
            showToast('success', response.message || 'Rejected successfully.');
            loadApprovals();
        },
        error: function (xhr) {
            showToast('error', xhr.responseJSON?.message || 'Failed to reject.');
        }
    });
}

function showDetails(id) {
    $.ajax({
        url: '{{ route("timesheets.approvals.details") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            timesheet_project_table_id: id
        },
        success: function (response) {
            const rows = response.daystotal || [];
            if (!rows.length) {
                $('#hoursDetailBody').html('<tr><td colspan="3" class="text-center">No hours found</td></tr>');
            } else {
                let html = '';
                rows.forEach(function (r) {
                    html += `<tr><td>${escapeHtml(r.date || '')}</td><td>${escapeHtml(String(r.totalhr ?? ''))}</td><td>${escapeHtml(r.note || '')}</td></tr>`;
                });
                $('#hoursDetailBody').html(html);
            }
            $('#hoursDetailModal').modal('show');
        },
        error: function (xhr) {
            showToast('error', xhr.responseJSON?.message || 'Failed to load details.');
        }
    });
}

function escapeHtml(value) {
    return $('<div/>').text(value).html();
}
</script>
@endpush

