@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<style>
    .ct-body {
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e2e2e2;
    }

    .ct-body .form-select,
    .ct-body .form-control {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 0.5rem 0.75rem;
        font-size: 0.95rem;
    }

    #reportContainer {
        display: none;
    }

    .validation-error {
        color: red;
        font-size: 0.8rem;
        margin-top: 2px;
    }

    .business-card .card-body {
        position: relative;
    }

    .open-status {
        background: #FBE7AE 0% 0% no-repeat padding-box;
        border-radius: 5px;
        color: #2B2B2B;
        padding: 5px 20px;
        font-weight: 600;
    }

    .support-info-label,
    .info-value {
        display: inline-block;
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
            <div class="px-4">
                <div class="header px-3 align-items-center pd_header">
                    <div>
                        <h1 class="pg-hd"><b>Support Statement</b></h1>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div>
                <div class="px-4 pb-4">
                    <div class="row m-0 mb-4">
                        <!-- Report Card (left) -->
                        <div class="col-lg-8">
                            <div id="reportContainer">
                                <div class="card business-card">
                                    <div class="card-body">
                                        <div class="print-status" onclick="printStatement()" style="cursor:pointer;">
                                            <span>Print</span>
                                        </div>
                                        <form id="printForm" method="POST" action="{{ route('support-statement.print') }}" target="_blank" style="display:none;">
                                            @csrf
                                            <input type="hidden" name="customer" id="printCustomer">
                                            <input type="hidden" name="project" id="printProject">
                                            <input type="hidden" name="from_date" id="printFromDate">
                                            <input type="hidden" name="to_date" id="printToDate">
                                        </form>
                                        <div class="mb-2 px-2">
                                            <div class="ss-hd">
                                                <div>
                                                    <img src="{{ asset('assets/img/logo-360x103.png') }}" alt="">
                                                </div>
                                                <h6 class="card-title fw-bold mb-0">Support Statement</h6>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="row m-0">
                                                <div class="col-lg-6">
                                                    <div class="p-0">
                                                        <div class="mb-2 d-flex flex-wrap">
                                                            <span class="support-info-label">Customer </span>
                                                            <span class="info-value" id="rptCustomer">-</span>
                                                        </div>
                                                        <div class="mb-2 d-flex flex-wrap">
                                                            <span class="support-info-label">Duration</span>
                                                            <span class="info-value" id="rptDuration">-</span>
                                                        </div>
                                                        <div class="mb-3 d-flex flex-wrap">
                                                            <span class="support-info-label">Project Id</span>
                                                            <span class="info-value" id="rptProjectId">-</span>
                                                        </div>
                                                        <div class="mb-3 d-flex flex-wrap">
                                                            <span class="support-info-label">Project name</span>
                                                            <span class="info-value" id="rptProjectName">-</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="p-3">
                                                        <div class="mb-2 d-flex flex-wrap">
                                                            <span class="support-info-label">Status</span>
                                                            <span class="info-value" id="rptStatus">-</span>
                                                        </div>
                                                        <div class="mb-2 d-flex flex-wrap">
                                                            <span class="support-info-label">Balance c/f</span>
                                                            <span class="info-value" id="rptBalance">-</span>
                                                        </div>
                                                        <div class="mb-3 d-flex flex-wrap">
                                                            <span class="support-info-label">Hours utilized</span>
                                                            <span class="info-value" id="rptUtilized">-</span>
                                                        </div>
                                                        <div class="mb-3 d-flex flex-wrap">
                                                            <span class="support-info-label">Closing balance</span>
                                                            <span class="info-value" id="rptClosing">-</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filter Form (right) -->
                        <div class="col-lg-4">
                            <div class="ct-body">
                                <form id="supportStatementForm">
                                    <div class="row g-3 mb-3">
                                        @if(!auth()->user()->inGroup(3))
                                        <div class="col-md-12">
                                            <select class="form-select" id="customerFilter">
                                                <option value="">Customer</option>
                                                @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}">{{ $customer->company }}</option>
                                                @endforeach
                                            </select>
                                            <span class="validation-error" id="customer_error"></span>
                                        </div>
                                        @else
                                        <input type="hidden" id="customerFilter" value="{{ $customer_id }}">
                                        @endif

                                        <div class="col-md-6">
                                            <select class="form-select" id="projectTypeFilter" onchange="updateProjectList()">
                                                <option value="">Project type</option>
                                                @foreach($project_types as $type)
                                                <option value="{{ $type->id }}">{{ $type->title }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <input type="text" class="form-control" id="fromDateFilter" placeholder="From" readonly>
                                            <span class="validation-error" id="fromdate_error"></span>
                                        </div>

                                        <div class="col-md-6">
                                            <select class="form-select" id="projectFilter">
                                                <option value="">Project</option>
                                                @foreach($projects as $project)
                                                <option value="{{ $project->id }}">{{ $project->title }} ({{ $project->project_id }})</option>
                                                @endforeach
                                            </select>
                                            <span class="validation-error" id="project_error"></span>
                                        </div>

                                        <div class="col-md-6">
                                            <input type="text" class="form-control" id="toDateFilter" placeholder="To" readonly>
                                            <span class="validation-error" id="todate_error"></span>
                                        </div>

                                        <div class="add-mail-right mt-3">
                                            <button type="button" class="btn btn-primary" id="submitBtn">Submit</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks Table -->
                    <div class="support-stmt-table" id="tasksTableContainer" style="display: none;">
                        <div>
                            <div class="sst-outer">
                                <div class="outer-tbl">
                                    <div class="table-container">
                                        <div class="table-responsive">
                                            <table class="table custom-table mb-0 t-for-ss">
                                                <thead>
                                                    <tr>
                                                        <th class="slno">Ticket no</th>
                                                        <th>Task name</th>
                                                        <th>Created at</th>
                                                        <th>Status</th>
                                                        <th>Consultant</th>
                                                        <th>Date</th>
                                                        <th>Hours</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tasksTableBody">
                                                    <tr>
                                                        <td colspan="7" class="text-center">Submit the form to view data</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
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
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize datepickers
        $('#fromDateFilter, #toDateFilter').datepicker({
            format: 'dd-mm-yyyy',
            autoclose: true
        });

        // Customer change - update projects
        $('#customerFilter').on('change', function() {
            updateProjectList();
        });

        // Submit button
        $('#submitBtn').on('click', function(e) {
            e.preventDefault();
            loadReport();
        });
    });

    function updateProjectList() {
        var customerId = $('#customerFilter').val();
        var projectType = $('#projectTypeFilter').val();

        $.ajax({
            url: '{{ route("support-statement.projects") }}',
            type: 'POST',
            data: {
                customerid: customerId,
                projecttype: projectType
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                var projectSelect = $('#projectFilter');
                projectSelect.empty().append('<option value="">Project</option>');

                if (response.projects) {
                    response.projects.forEach(function(project) {
                        projectSelect.append('<option value="' + project.id + '">' + project.title + ' (' + project.project_id + ')</option>');
                    });
                }
            }
        });
    }

    function printStatement() {
        var customer = $('#customerFilter').val();
        var project = $('#projectFilter').val();
        var fromDate = $('#fromDateFilter').val();
        var toDate = $('#toDateFilter').val();

        if (!customer || !project || !fromDate || !toDate) {
            alert('Please submit the form first to load data');
            return;
        }

        $('#printCustomer').val(customer);
        $('#printProject').val(project);
        $('#printFromDate').val(fromDate);
        $('#printToDate').val(toDate);
        $('#printForm').submit();
    }

    function loadReport() {
        // Clear errors
        $('.validation-error').html('');

        var customer = $('#customerFilter').val();
        var project = $('#projectFilter').val();
        var fromDate = $('#fromDateFilter').val();
        var toDate = $('#toDateFilter').val();

        // Validate
        if (!customer) {
            $('#customer_error').html('Please select Customer');
            return false;
        }
        if (!project) {
            $('#project_error').html('Please select Project');
            return false;
        }
        if (!fromDate) {
            $('#fromdate_error').html('Please select date');
            return false;
        }
        if (!toDate) {
            $('#todate_error').html('Please select date');
            return false;
        }

        $.ajax({
            url: '{{ route("support-statement.report") }}',
            type: 'POST',
            data: {
                customer: customer,
                project: project,
                from_date: fromDate,
                to_date: toDate
            },
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                // Update report card
                $('#rptCustomer').text(response.customer ? response.customer.company : '-');
                $('#rptDuration').text(response.from_date + ' to ' + response.to_date);
                $('#rptProjectId').text(response.project ? response.project.project_id : '-');
                $('#rptProjectName').text(response.project ? response.project.title : '-');
                $('#rptStatus').text(response.project ? response.project.project_status : '-');
                $('#rptBalance').text(response.balance_cf);
                $('#rptUtilized').text(response.hours_utilized);
                $('#rptClosing').text(response.closing_balance);

                // Show report card
                $('#reportContainer').show();

                // Render tasks table
                renderTasks(response.tasks);
                $('#tasksTableContainer').show();
            },
            error: function(xhr) {
                console.error('Error loading report:', xhr);
                alert('Error loading report');
            }
        });
    }

    function renderTasks(tasks) {
        var tbody = $('#tasksTableBody');
        tbody.empty();

        if (!tasks || tasks.length === 0) {
            tbody.append('<tr><td colspan="7" class="text-center">No tasks found</td></tr>');
            return;
        }

        tasks.forEach(function(task) {
            if (task.hours.length === 0) return;

            // First row with task info + first hour entry
            var firstHour = task.hours[0];
            var statusClass = task.status.toLowerCase().includes('close') || task.status.toLowerCase().includes('complete') ? 'closed-status' : 'open-status';

            var firstRow = '<tr>' +
                '<td class="ticket-number slno">' + task.ticket_no + '</td>' +
                '<td class="ticket-report">' + task.title + '</td>' +
                '<td class="ticket-cdate">' + task.created_at + '</td>' +
                '<td><div><span class="' + statusClass + '">' + task.status + '</span></div></td>' +
                '<td class="consultant-name">' + firstHour.consultant + '</td>' +
                '<td class="consultant-date">' + firstHour.date + '</td>' +
                '<td class="consultant-hrs">' + firstHour.totalhr + '</td>' +
                '</tr>';
            tbody.append(firstRow);

            // Remaining hour entries
            for (var i = 1; i < task.hours.length; i++) {
                var hourRow = '<tr>' +
                    '<td></td><td></td><td></td><td></td>' +
                    '<td class="consultant-name">' + task.hours[i].consultant + '</td>' +
                    '<td class="consultant-date">' + task.hours[i].date + '</td>' +
                    '<td class="consultant-hrs">' + task.hours[i].totalhr + '</td>' +
                    '</tr>';
                tbody.append(hourRow);
            }

            // Total row for this task
            var totalRow = '<tr class="total-row">' +
                '<td></td><td></td><td></td><td></td>' +
                '<td class="ttl-hrs"></td>' +
                '<td class="ttl-hrs"><strong>Total Hours</strong></td>' +
                '<td class="ttl-hrs"><strong>' + task.total_hours + '</strong></td>' +
                '</tr>';
            tbody.append(totalRow);
        });
    }
</script>
@endpush
