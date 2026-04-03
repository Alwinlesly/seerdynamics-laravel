@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
<style>
/* ===== Create Weekly Timesheet Styles (matching provided design) ===== */

/* Prevent full-page horizontal overflow on create screen */
.timesheet-create-page .right-section {
    min-width: 0;
}

.timesheet-create-page {
    overflow-x: hidden;
}

.timesheet-create-page #maindiv .table-responsive.table-for-create {
    overflow-x: auto;
    overflow-y: hidden;
    max-width: 100%;
    display: block;
    width: 100%;
}

.timesheet-create-page #maindiv .timesheet-grid-scroll {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}

.timesheet-create-page #maindiv .timesheet-table {
    width: max-content;
    min-width: 100%;
}

.timesheet-create-page #maindiv .timesheet-table th,
.timesheet-create-page #maindiv .timesheet-table td {
    white-space: nowrap;
}

/* Timesheet container */
.timesheet-container {
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

/* Header controls (New Line / Remove Line buttons) */
.header-controls {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}

.btn-add-line {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid #513998;
    background: #fff;
    color: #513998;
    transition: background 0.15s;
}
.btn-add-line:hover { background: #f0edfa; }

.btn-remove-line {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    border: 1px solid #dc3545;
    background: #fff;
    color: #dc3545;
    transition: background 0.15s;
}
.btn-remove-line:hover { background: #fff5f5; }

/* Timesheet table */
.timesheet-table {
    border-collapse: collapse;
    width: 100%;
    font-size: 13px;
}

.timesheet-table th {
    background: #F5F5F5;
    color: #2B2B2B;
    font-weight: 600;
    text-align: center;
    padding: 10px 8px;
    border: 1px solid #dee2e6;
    white-space: nowrap;
}

.timesheet-table td {
    padding: 6px 5px;
    border: 1px solid #dee2e6;
    vertical-align: middle;
}

/* Force horizontal scroll to appear inside grid wrapper */
.timesheet-create-page #maindiv .timesheet-grid-scroll .timesheet-table {
    width: max-content !important;
    min-width: 100% !important;
}

.checkbox-col { width: 40px; text-align: center; }
.customer-col { min-width: 140px; }
.project-col  { min-width: 140px; }
.ticket-col   { min-width: 140px; }
.date-col     { width: 70px; text-align: center; }
.billable-col { min-width: 140px; }

/* dashed left border to separate checkbox from customer col */
.br-col { border-right: 2px solid #c0b4e8 !important; }

.timesheet-table .form-select {
    font-size: 12px;
    padding: 4px 8px;
    min-width: 110px;
    background-color: #fff;
    border: 1px solid #dee2e6;
    color: #2B2B2B;
    width: 100%;
    max-width: 100%;
    padding-right: 2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Prevent row select values from overflowing table cells */
.timesheet-table td.customer-col,
.timesheet-table td.project-col,
.timesheet-table td.ticket-col {
    max-width: 190px;
}

/* Hour input inside date cells */
.hour-input {
    width: 52px;
    height: 36px;
    text-align: center;
    font-size: 13px;
    padding: 4px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin: 0 auto;
    display: block;
}
.hour-input:focus {
    border-color: #7d6bb2;
    outline: none;
    box-shadow: 0 0 0 0.15rem rgba(125,107,178,.2);
}

.note-link {
    display: block;
    width: 100%;
    margin-top: 4px;
    line-height: 1;
    text-align: center;
    color: #513998;
    font-size: 12px;
    text-decoration: none;
}
.note-link:hover { color: #3e2d79; }

/* Save/Submit buttons */
.sv-draft {
    padding: 8px 22px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    background: #f0edfa;
    color: #513998;
    border: 1px solid #c0b4e8;
    transition: background 0.15s;
}
.sv-draft:hover { background: #e5e0f5; }

.sv-sub {
    padding: 8px 22px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    background: #513998;
    color: #fff;
    border: none;
    transition: opacity 0.15s;
}
.sv-sub:hover { opacity: 0.88; }

/* Date pickers – make addon look cohesive */
.input-group-addon {
    display: flex;
    align-items: center;
    padding: 0 10px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-left: 0;
    border-radius: 0 6px 6px 0;
    cursor: pointer;
}

/* Nav breadcrumb */
.pg-nv { font-size: 13px; color: #888; margin-top: 2px; }
.pg-nv a { color: #888; text-decoration: none; }
.pg-nv .activePage { color: #513998; }

/* table-for-create outer wrapper */
.table-for-create {
    padding: 0;
}

/* Keep top filter row spacious and prevent overlaps */
.create-ticket-body {
    padding: 0 !important;
}

.ctb-row {
    width: 100% !important;
    margin-left: 0;
    margin-right: 0;
}

#timesheetForm {
    width: 100%;
    max-width: 100%;
}

/* Top row: keep Consultant/Start/End equal and aligned to available width */
.timesheet-create-page .ctb-row > .col-md-4 {
    display: flex;
    flex-direction: column;
}

.timesheet-create-page .ctb-row > .col-md-4 > .form-select,
.timesheet-create-page .ctb-row > .col-md-4 > .input-group.date {
    width: 100%;
    max-width: 100%;
}

.ctb-row .form-select,
.ctb-row .form-control {
    width: 100%;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ctb-row .input-group.date {
    position: relative;
}

.ctb-row .input-group.date .form-control {
    padding-right: 2.4rem;
}

.ctb-row .input-group.date .input-group-addon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 3;
    border: 0;
    background: transparent;
    padding: 0;
}

</style>
@endpush

@section('content')

{{-- Note Modal --}}
<div class="modal fade" id="notemodal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rowid">
                <input type="hidden" id="colid">
                <textarea class="form-control" id="modalnote" rows="4"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="saveNoteBtn">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="main timesheet-create-page">
    {{-- Top Header --}}
    @include('partials.header')

    <div class="row m-0">
        @include('partials.sidebar')

        <div class="right-section col-lg-9 px-0 pb-0">
            <div class="px-4">
                {{-- Page heading --}}
                <div class="header">
                    <div>
                        <h1 class="pg-hd"><b>Timesheet</b></h1>
                        <div class="pg-nv">
                            <span><a href="{{ route('timesheets.index') }}">Timesheet</a></span>
                            <span class="activePage">/ Create</span>
                        </div>
                    </div>
                </div>

                {{-- Form --}}
                <div class="sel-wrapper create-ticket-body px-0">
                    <form id="timesheetForm" action="{{ route('timesheets.store') }}" method="POST">
                        @csrf
                        <input type="hidden" id="rowindex" name="rowindex" value="1">
                        <input type="hidden" id="colindex" name="colindex" value="1">
                        <input type="hidden" name="submit_or_draft" id="submit_or_draft" value="draft">

                        {{-- Filters row: Consultant + Start date + End date --}}
                        <div class="row g-3 mb-3 ctb-row">

                            @if(auth()->user()->inGroup(1))
                            <div class="col-md-4">
                                <label class="form-label">Consultant</label>
                                <select class="form-select" id="user_id" name="user_id"
                                        onchange="seteachdayhrtotal()" required>
                                    <option value="">Select Consultant</option>
                                    @foreach($system_users as $su)
                                        <option value="{{ $su->id }}">
                                            {{ $su->first_name }} {{ $su->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @else
                            <input type="hidden" name="user_id" id="user_id" value="{{ auth()->id() }}">
                            @endif

                            {{-- Start date --}}
                            <div class="col-md-4">
                                <label for="issueDate" class="form-label">Start date</label>
                                <div id="startDatepicker" class="input-group date" data-date-format="dd-mm-yyyy">
                                    <input class="form-control" id="starting_time" name="start_date"
                                           type="text" readonly placeholder="Select start date" required>
                                    <span class="input-group-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 24 24" fill="none">
                                            <path d="M7.75 2.5C7.75 2.08579 7.41421 1.75 7 1.75C6.58579 1.75 6.25 2.08579 6.25 2.5V4.07926C4.81067 4.19451 3.86577 4.47737 3.17157 5.17157C2.47737 5.86577 2.19451 6.81067 2.07926 8.25H21.9207C21.8055 6.81067 21.5226 5.86577 20.8284 5.17157C20.1342 4.47737 19.1893 4.19451 17.75 4.07926V2.5C17.75 2.08579 17.4142 1.75 17 1.75C16.5858 1.75 16.25 2.08579 16.25 2.5V4.0129C15.5847 4 14.839 4 14 4H10C9.16097 4 8.41527 4 7.75 4.0129V2.5Z" fill="#1C274C"/>
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M2 12C2 11.161 2 10.4153 2.0129 9.75H21.9871C22 10.4153 22 11.161 22 12V14C22 17.7712 22 19.6569 20.8284 20.8284C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.8284C2 19.6569 2 17.7712 2 14V12ZM17 14C17.5523 14 18 13.5523 18 13C18 12.4477 17.5523 12 17 12C16.4477 12 16 12.4477 16 13C16 13.5523 16.4477 14 17 14ZM17 18C17.5523 18 18 17.5523 18 17C18 16.4477 17.5523 16 17 16C16.4477 16 16 16.4477 16 17C16 17.5523 16.4477 18 17 18ZM13 13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13C11 12.4477 11.4477 12 12 12C12.5523 12 13 12.4477 13 13ZM13 17C13 17.5523 12.5523 18 12 18C11.4477 18 11 17.5523 11 17C11 16.4477 11.4477 16 12 16C12.5523 16 13 16.4477 13 17ZM7 14C7.55228 14 8 13.5523 8 13C8 12.4477 7.55228 12 7 12C6.44772 12 6 12.4477 6 13C6 13.5523 6.44772 14 7 14ZM7 18C7.55228 18 8 17.5523 8 17C8 16.4477 7.55228 16 7 16C6.44772 16 6 16.4477 6 17C6 17.5523 6.44772 18 7 18Z" fill="#1C274C"/>
                                        </svg>
                                    </span>
                                </div>
                            </div>

                            {{-- End date --}}
                            <div class="col-md-4">
                                <label for="issueDate" class="form-label">End date</label>
                                <div id="endDatepicker" class="input-group date" data-date-format="dd-mm-yyyy">
                                    <input class="form-control" id="end_time" name="end_date"
                                           type="text" readonly placeholder="Auto-filled">
                                    <span class="input-group-addon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 24 24" fill="none">
                                            <path d="M7.75 2.5C7.75 2.08579 7.41421 1.75 7 1.75C6.58579 1.75 6.25 2.08579 6.25 2.5V4.07926C4.81067 4.19451 3.86577 4.47737 3.17157 5.17157C2.47737 5.86577 2.19451 6.81067 2.07926 8.25H21.9207C21.8055 6.81067 21.5226 5.86577 20.8284 5.17157C20.1342 4.47737 19.1893 4.19451 17.75 4.07926V2.5C17.75 2.08579 17.4142 1.75 17 1.75C16.5858 1.75 16.25 2.08579 16.25 2.5V4.0129C15.5847 4 14.839 4 14 4H10C9.16097 4 8.41527 4 7.75 4.0129V2.5Z" fill="#1C274C"/>
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M2 12C2 11.161 2 10.4153 2.0129 9.75H21.9871C22 10.4153 22 11.161 22 12V14C22 17.7712 22 19.6569 20.8284 20.8284C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.8284C2 19.6569 2 17.7712 2 14V12ZM17 14C17.5523 14 18 13.5523 18 13C18 12.4477 17.5523 12 17 12C16.4477 12 16 12.4477 16 13C16 13.5523 16.4477 14 17 14ZM17 18C17.5523 18 18 17.5523 18 17C18 16.4477 17.5523 16 17 16C16.4477 16 16 16.4477 16 17C16 17.5523 16.4477 18 17 18ZM13 13C13 13.5523 12.5523 14 12 14C11.4477 14 11 13.5523 11 13C11 12.4477 11.4477 12 12 12C12.5523 12 13 12.4477 13 13ZM13 17C13 17.5523 12.5523 18 12 18C11.4477 18 11 17.5523 11 17C11 16.4477 11.4477 16 12 16C12.5523 16 13 16.4477 13 17ZM7 14C7.55228 14 8 13.5523 8 13C8 12.4477 7.55228 12 7 12C6.44772 12 6 12.4477 6 13C6 13.5523 6.44772 14 7 14ZM7 18C7.55228 18 8 17.5523 8 17C8 16.4477 7.55228 16 7 16C6.44772 16 6 16.4477 6 17C6 17.5523 6.44772 18 7 18Z" fill="#1C274C"/>
                                        </svg>
                                    </span>
                                </div>
                            </div>

                        </div>{{-- end row --}}

                        {{-- Dynamic timesheet table (hidden until dates selected) --}}
                        <div id="maindiv" style="display:none;">

                            <div class="table-responsive table-x table-for-create">

                                <div class="timesheet-container">

                                    {{-- New line / Remove line --}}
                                    <div class="header-controls">
                                        <button type="button" class="btn-add-line" id="newrow">
                                            <i class="bi bi-plus"></i> New line
                                        </button>
                                        <button type="button" class="btn-remove-line" id="btndelete">
                                            <i class="bi bi-trash"></i> Remove line
                                        </button>
                                    </div>

                                    <div class="timesheet-grid-scroll">
                                        {{-- Timesheet table --}}
                                        <table class="table timesheet-table" id="timesheettable">
                                            <thead>
                                                <tr id="tableHeaderRow">
                                                    <th class="checkbox-col br-col"></th>
                                                    <th class="customer-col">Customer</th>
                                                    <th class="project-col">Project</th>
                                                    <th class="ticket-col">Ticket</th>
                                                    {{-- Date headers inserted dynamically by JS --}}
                                                    <th class="billable-col">Billable<br>/Non-billable</th>
                                                </tr>
                                            </thead>
                                            <tbody id="timesheetTableBody">
                                                {{-- Rows inserted dynamically by JS --}}
                                            </tbody>
                                        </table>
                                    </div>

                                </div>{{-- .timesheet-container --}}

                                {{-- Save / Submit buttons --}}
                                <div>
                                    <div class="d-flex align-items-center gap-2 pagination flex-wrap">
                                        <div>
                                            <button type="button" class="sv-draft" id="saveDraftBtn">
                                                Save as draft
                                            </button>
                                        </div>
                                        <div>
                                            <button type="button" class="sv-sub" id="submitBtn">
                                                Submit
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>{{-- .table-responsive --}}

                        </div>{{-- #maindiv --}}

                    </form>
                </div>{{-- .sel-wrapper --}}
            </div>

            @include('partials.footer')

        </div>{{-- .right-section --}}
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script>
const BASE_URL  = '{{ url('/') }}/';
const CSRF_TOKEN = '{{ csrf_token() }}';
const weekday   = ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];

// ─────────────────────────────────────────────
// Datepickers
// ─────────────────────────────────────────────
$(function () {
    $('#startDatepicker').datepicker({
        format   : 'dd-mm-yyyy',
        autoclose: true,
    }).on('changeDate', function (e) {
        const startDate = e.date;

        // Auto-fill end date = start + 6 days (7-day week)
        const endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + 6);

        const pad = n => String(n).padStart(2, '0');
        const fmt = d => pad(d.getDate()) + '-' + pad(d.getMonth() + 1) + '-' + d.getFullYear();

        $('#end_time').val(fmt(endDate));

        // Lock the pickers after selection
        $('#starting_time').prop('disabled', true);
        $('#end_time').prop('disabled', true);

        setentryrows();
    });

    // Allow manual end-date pick if needed
    $('#endDatepicker').datepicker({
        format   : 'dd-mm-yyyy',
        autoclose: true,
    }).on('changeDate', function () {
        setentryrows();
    });
});

// ─────────────────────────────────────────────
// Build timesheet table after dates are chosen
// ─────────────────────────────────────────────
function parseDD_MM_YYYY(s) {
    const [d, m, y] = s.split('-');
    return new Date(+y, +m - 1, +d);
}

function setentryrows() {
    const startVal = $('#starting_time').val();
    const endVal   = $('#end_time').val();
    if (!startVal || !endVal) return;

    const date1 = parseDD_MM_YYYY(startVal);
    const date2 = parseDD_MM_YYYY(endVal);

    const dayDiff = Math.round((date2 - date1) / 86400000);
    const cols    = dayDiff + 1;

    document.getElementById('colindex').value = cols;
    document.getElementById('rowindex').value  = 1;

    // Rebuild date headers
    buildDateHeaders(date1, cols);

    // Build first body row
    const tbody = document.getElementById('timesheetTableBody');
    tbody.innerHTML = '';
    tbody.appendChild(buildRow(1, cols));

    document.getElementById('maindiv').style.display = 'block';

    loadCustomersForRow(1);
    seteachdayhrtotal();
}

function buildDateHeaders(startDate, cols) {
    const headerRow = document.getElementById('tableHeaderRow');

    // Remove previously inserted date <th>s
    headerRow.querySelectorAll('.date-th').forEach(el => el.remove());

    const billableTh = headerRow.querySelector('.billable-col');
    let d = new Date(startDate);

    for (let k = 1; k <= cols; k++) {
        const th = document.createElement('th');
        th.className = 'date-col date-th';
        th.innerHTML =
            weekday[d.getDay()] + '<br>' +
            d.getDate() + '/' + (d.getMonth() + 1) +
            `<input type="hidden" name="date_${k}" value="${d.getFullYear()}-${d.getMonth()+1}-${d.getDate()}">` +
            `<input type="hidden" name="day_${k}"  value="${weekday[d.getDay()]}">`;
        headerRow.insertBefore(th, billableTh);
        d.setDate(d.getDate() + 1);
    }
}

function buildRow(rowNum, cols) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="checkbox-col">
            <input type="checkbox" class="form-check-input row-checkbox" value="${rowNum}">
        </td>
        <td class="customer-col dashed-border">
            <select class="form-select" id="customer_${rowNum}" name="customer_${rowNum}"
                    onchange="loadProjectsForRow(${rowNum})">
                <option value="">Select Customer</option>
            </select>
        </td>
        <td class="project-col">
            <select class="form-select" id="project_id_${rowNum}" name="project_id_${rowNum}"
                    onchange="loadTicketsForRow(${rowNum})">
                <option value="">Select Project</option>
            </select>
        </td>
        <td class="ticket-col">
            <select class="form-select" id="task_id_${rowNum}" name="task_id_${rowNum}">
                <option value="">Select Ticket</option>
            </select>
        </td>`;

    for (let j = 1; j <= cols; j++) {
        tr.innerHTML += `
        <td class="date-col text-center">
            <input type="number" class="hour-input"
                   name="totalhour_${rowNum}_${j}" id="totalhour_${rowNum}_${j}"
                   min="0" max="24" step="0.5" value="">
            <a href="#" class="note-link note-trigger" data-row="${rowNum}" data-col="${j}" title="Add note">
                <i class="bi bi-sticky"></i>
            </a>
            <textarea id="note_${rowNum}_${j}" name="note_${rowNum}_${j}"
                      style="display:none;"></textarea>
        </td>`;
    }

    tr.innerHTML += `
        <td class="billable-col">
            <select class="form-select" name="billable_not_${rowNum}" id="billable_not_${rowNum}">
                <option value="1">Billable</option>
                <option value="0">Non-billable</option>
            </select>
        </td>`;

    return tr;
}

// ─────────────────────────────────────────────
// New row
// ─────────────────────────────────────────────
document.getElementById('newrow').addEventListener('click', function () {
    const startVal = $('#starting_time').val();
    const endVal   = $('#end_time').val();
    if (!startVal || !endVal) return;

    const date1 = parseDD_MM_YYYY(startVal);
    const date2 = parseDD_MM_YYYY(endVal);
    const cols  = Math.round((date2 - date1) / 86400000) + 1;

    const currentIdx = parseInt(document.getElementById('rowindex').value);
    const newRowNum  = currentIdx + 1;
    document.getElementById('rowindex').value = newRowNum;

    document.getElementById('timesheetTableBody').appendChild(buildRow(newRowNum, cols));
    loadCustomersForRow(newRowNum);
});

// ─────────────────────────────────────────────
// Remove selected rows
// ─────────────────────────────────────────────
document.getElementById('btndelete').addEventListener('click', function () {
    document.querySelectorAll("#timesheettable tbody .row-checkbox:checked")
        .forEach(cb => cb.closest('tr').remove());
});

// ─────────────────────────────────────────────
// AJAX helpers
// ─────────────────────────────────────────────
function loadCustomersForRow(i) {
    $.ajax({
        type    : 'GET',
        url     : BASE_URL + 'timesheet/customers',
        dataType: 'json',
        success : function (data) {
            let opts = '<option value="">Select Customer</option>';
            (data.system_clients || []).forEach(function (c) {
                if (c.company) opts += `<option value="${c.id}">${c.company}</option>`;
            });
            $('#customer_' + i).html(opts);
        }
    });
}

function loadProjectsForRow(i) {
    const customerId = $('#customer_' + i).val();
    $.ajax({
        type    : 'POST',
        url     : BASE_URL + 'timesheet/projects-by-customer',
        data    : { _token: CSRF_TOKEN, customerid: customerId },
        dataType: 'json',
        success : function (data) {
            let opts = '<option value="">Select Project</option>';
            (data.projects || []).forEach(function (p) {
                opts += `<option value="${p.id}">${p.project_id} (${p.title})</option>`;
            });
            $('#project_id_' + i).html(opts);
            $('#task_id_' + i).html('<option value="">Select Ticket</option>');
        }
    });
}

function loadTicketsForRow(i) {
    const projectId = $('#project_id_' + i).val();
    $.ajax({
        type    : 'POST',
        url     : BASE_URL + 'timesheet/tasks-by-project',
        data    : { _token: CSRF_TOKEN, project_id: projectId },
        dataType: 'json',
        success : function (data) {
            let opts = '<option value="">Select Ticket</option>';
            (data.data || []).forEach(function (t) {
                const key = '#' + String(t.id).padStart(5, '0');
                opts += `<option value="${t.id}">${key} (${t.title})</option>`;
            });
            $('#task_id_' + i).html(opts);
        }
    });
}

function seteachdayhrtotal() {
    // Placeholder – Week Summary panel can be added later
}

// ─────────────────────────────────────────────
// Note modal
// ─────────────────────────────────────────────
document.getElementById('saveNoteBtn').addEventListener('click', function () {
    const remarks = document.getElementById('modalnote').value;
    const i = document.getElementById('rowid').value;
    const j = document.getElementById('colid').value;
    if (i && j) {
        const el = document.getElementById(`note_${i}_${j}`);
        if (el) el.value = remarks;
    }
    const m = bootstrap.Modal.getInstance(document.getElementById('notemodal'));
    if (m) m.hide();
});

document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.note-trigger');
    if (!trigger) return;
    e.preventDefault();

    const i = trigger.getAttribute('data-row');
    const j = trigger.getAttribute('data-col');
    const noteEl = document.getElementById(`note_${i}_${j}`);

    document.getElementById('rowid').value = i;
    document.getElementById('colid').value = j;
    document.getElementById('modalnote').value = noteEl ? (noteEl.value || '') : '';

    const modal = new bootstrap.Modal(document.getElementById('notemodal'));
    modal.show();
});

// ─────────────────────────────────────────────
// Form submission (AJAX)
// ─────────────────────────────────────────────
document.getElementById('saveDraftBtn').addEventListener('click', function () {
    document.getElementById('submit_or_draft').value = 'draft';
    submitTimesheetForm();
});

document.getElementById('submitBtn').addEventListener('click', function () {
    document.getElementById('submit_or_draft').value = 'submit';
    submitTimesheetForm();
});

function submitTimesheetForm() {
    // Re-enable disabled inputs so they are included in FormData
    document.getElementById('starting_time').disabled = false;
    document.getElementById('end_time').disabled       = false;

    const formData = new FormData(document.getElementById('timesheetForm'));

    $.ajax({
        type       : 'POST',
        url        : '{{ route("timesheets.store") }}',
        data       : formData,
        processData: false,
        contentType: false,
        dataType   : 'json',
        success    : function (res) {
            if (res.error === false) {
                showToast('success', res.message || 'Timesheet saved successfully.');
                setTimeout(function () {
                    window.location.href = '{{ route("timesheets.index") }}';
                }, 1200);
            } else {
                showToast('error', res.message || 'An error occurred.');
                // re-lock date fields
                document.getElementById('starting_time').disabled = true;
                document.getElementById('end_time').disabled       = true;
            }
        },
        error      : function (xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Server error. Please try again.';
            showToast('error', msg);
            document.getElementById('starting_time').disabled = true;
            document.getElementById('end_time').disabled       = true;
        }
    });
}


// ─────────────────────────────────────────────
// Sidebar toggle (matches other pages)
// ─────────────────────────────────────────────
$(document).ready(function () {
    const sidebar  = document.getElementById('sidebar');
    const hamburger = document.querySelector('.hamburger');
    if (hamburger && sidebar) {
        hamburger.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
        });
        document.addEventListener('click', function (e) {
            if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    }
});
</script>
@endpush
