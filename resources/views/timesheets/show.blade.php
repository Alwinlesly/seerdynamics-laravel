@extends('layouts.app')

@push('styles')
<style>
.pg-nv { font-size: 13px; color: #888; margin-top: 2px; }
.pg-nv a { color: #888; text-decoration: none; }
.pg-nv .activePage { color: #513998; }

.view-ts-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px 24px;
    margin-bottom: 20px;
}
.view-ts-card .ts-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    margin-bottom: 16px;
    font-size: 14px;
}
.view-ts-card .ts-meta .meta-item label {
    font-weight: 600;
    color: #555;
    display: block;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.view-ts-card .ts-meta .meta-item span {
    font-size: 15px;
    color: #2B2B2B;
}

.ts-view-table {
    width: 100%;
    font-size: 13px;
    border-collapse: collapse;
}
.ts-view-table th {
    background: #F5F5F5;
    color: #2B2B2B;
    font-weight: 600;
    text-align: center;
    padding: 10px 8px;
    border: 1px solid #dee2e6;
    white-space: nowrap;
}
.ts-view-table td {
    padding: 8px 10px;
    border: 1px solid #dee2e6;
    vertical-align: middle;
    text-align: center;
}
.ts-view-table td.left-td { text-align: left; }

.badge-draft     { background: #f0edfa; color: #513998; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
.badge-submitted { background: #e3f0fd; color: #1565c0; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
.badge-approved  { background: #e8f5e9; color: #2e7d32; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
.badge-returned  { background: #fff3e0; color: #e65100; padding: 4px 12px; border-radius: 20px; font-size: 12px; }

.btn-back {
    background: #f0edfa;
    color: #513998;
    border: 1px solid #c0b4e8;
    padding: 7px 20px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-back:hover { background: #e5e0f5; color: #513998; }
</style>
@endpush

@section('content')
<div class="main">
    @include('partials.header')

    <div class="row m-0">
        @include('partials.sidebar')

        <div class="right-section col-lg-9 px-0 pb-0">
            <div class="px-4">
                <div class="header">
                    <div>
                        <h1 class="pg-hd"><b>Timesheet</b></h1>
                        <div class="pg-nv">
                            <span><a href="{{ route('timesheets.index') }}">Timesheet</a></span>
                            <span class="activePage">/ View</span>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('timesheets.index') }}" class="btn-back">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                {{-- Header card --}}
                <div class="view-ts-card">
                    <div class="ts-meta">
                        <div class="meta-item">
                            <label>Timesheet ID</label>
                            <span>T{{ str_pad($timesheet->id, 5, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="meta-item">
                            <label>Consultant</label>
                            <span>{{ $owner->first_name ?? '' }} {{ $owner->last_name ?? '' }}</span>
                        </div>
                        <div class="meta-item">
                            <label>Work Week</label>
                            <span>{{ $timesheet->work_week }}</span>
                        </div>
                        <div class="meta-item">
                            <label>Start Date</label>
                            <span>{{ date('d-m-Y', strtotime($timesheet->start_date)) }}</span>
                        </div>
                        <div class="meta-item">
                            <label>End Date</label>
                            <span>{{ date('d-m-Y', strtotime($timesheet->end_date)) }}</span>
                        </div>
                        <div class="meta-item">
                            <label>Status</label>
                            <span>
                                @if($timesheet->submit_or_draft === 'draft')
                                    <span class="badge-draft">Draft</span>
                                @else
                                    <span class="badge-submitted">Submitted</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Timesheet entries table --}}
                <div class="table-responsive table-x">
                    <table class="table ts-view-table">
                        <thead>
                            <tr>
                                <th class="text-start">Customer</th>
                                <th class="text-start">Project</th>
                                <th class="text-start">Ticket</th>
                                @foreach($dates as $date)
                                    <th>
                                        {{ date('D', strtotime($date)) }}<br>
                                        {{ date('d/m', strtotime($date)) }}
                                    </th>
                                @endforeach
                                <th>Billable?</th>
                                <th>Total Hrs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($timesheetprojects as $proj)
                            <tr>
                                <td class="left-td">{{ $proj->customer_company ?? '–' }}</td>
                                <td class="left-td">
                                    {{ $proj->project_code ?? '' }}
                                    @if($proj->project_title)
                                        <small class="text-muted">({{ $proj->project_title }})</small>
                                    @endif
                                </td>
                                <td class="left-td">
                                    @if($proj->task_title)
                                        <small>#{{ str_pad($proj->task_id, 5, '0', STR_PAD_LEFT) }}</small>
                                        {{ $proj->task_title }}
                                    @else
                                        –
                                    @endif
                                </td>
                                @php $totalHrs = 0; @endphp
                                @foreach($dates as $date)
                                    @php
                                        $hrRow = $proj->hours->firstWhere('date', $date);
                                        $hrs   = $hrRow ? (float) $hrRow->hours : 0.0;
                                        $totalHrs += $hrs;
                                    @endphp
                                    <td>{{ $hrs > 0 ? $hrs : '–' }}</td>
                                @endforeach
                                <td>{{ $proj->billable ? 'Billable' : 'Non-billable' }}</td>
                                <td><strong>{{ $totalHrs }}</strong></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ count($dates) + 4 }}" class="text-center text-muted">
                                    No entries found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

            @include('partials.footer')
        </div>
    </div>
</div>
@endsection
