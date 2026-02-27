<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Support Statement - Print</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
        table { border-spacing: 0; border-collapse: collapse; }
        td { padding: 3px 8px !important; background-color: #fff; }
        th { padding: 5px 8px !important; background-color: #f8f9fa; }
        @page { margin: 10px; padding: 0; }
        .list-table td, .list-table th { border: 1px solid #eee; background-color: #fff; }
        .task-group { page-break-inside: avoid; }
        .no-page-break { page-break-inside: avoid; page-break-after: avoid; }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
        }
        .print-btn-bar { text-align: right; padding: 10px 20px; }
        .print-btn-bar button {
            background: #513998; color: #fff; border: none; padding: 8px 30px;
            border-radius: 5px; cursor: pointer; font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="no-print print-btn-bar">
        <button onclick="window.print()">Print / Download PDF</button>
    </div>

    <section style="padding: 10px 20px;">
        <table class="table table-sm" style="background-color: #fff; width: 100%;">
            <tr style="background-color: #fff;">
                <td style="width:20%;">
                    <img src="{{ asset('assets/img/logo-360x103.png') }}" style="margin-top: 20px; width: 100px;">
                </td>
                <td style="width:30%;">
                    <h3 style="margin-top: 20px; font-size: 18px; margin-left: 20px;">Support Statement</h3>
                </td>
                <td style="width:50%;">
                    <table class="table table-sm" style="font-size: 11px;">
                        <tr><td>Customer:</td><td>{{ $customer->company ?? '-' }}</td></tr>
                        <tr><td>Duration:</td><td>{{ $from_date }} - {{ $to_date }}</td></tr>
                        <tr><td>Project ID:</td><td>{{ $project->project_id ?? '-' }}</td></tr>
                        <tr><td>Project Name:</td><td>{{ $project->title ?? '-' }}</td></tr>
                        <tr><td>Status:</td><td>{{ $project->project_status ?? '-' }}</td></tr>
                        <tr><td>Balance c/f:</td><td>{{ $balance_cf }}</td></tr>
                        <tr><td>Hours Utilized:</td><td>{{ $hours_utilized }}</td></tr>
                        <tr><td>Closing Balance:</td><td>{{ $closing_balance }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table list-table table-sm" style="width: 98%;">
                        <thead>
                            <tr>
                                <th style="width:7%; text-align:center;">Ticket No</th>
                                <th style="width:25%; text-align:center;">Task Name</th>
                                <th style="width:13%; text-align:center;">Created at</th>
                                <th style="width:10%; text-align:center;">Status</th>
                                <th style="width:20%; text-align:center;">Consultant</th>
                                <th style="width:15%; text-align:center;">Date</th>
                                <th style="width:5%; text-align:center;">Hours</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tasks as $task)
                            @php
                                $isFirstRow = true;
                            @endphp
                            @foreach($task['hours'] as $index => $hour)
                            <tr class="{{ $isFirstRow ? 'task-group' : '' }}">
                                @if($isFirstRow)
                                <td style="width:7%; text-align:center;">{{ $task['ticket_no'] }}</td>
                                <td style="width:25%;">{{ $task['title'] }}</td>
                                <td style="width:13%; text-align:center;">{{ $task['created_at'] }}</td>
                                <td style="width:10%; text-align:center;">{{ $task['status'] }}</td>
                                @else
                                <td style="width:7%;"></td>
                                <td style="width:25%;"></td>
                                <td style="width:13%;"></td>
                                <td style="width:10%;"></td>
                                @endif
                                <td style="width:20%; text-align:center;">{{ $hour['consultant'] }}</td>
                                <td style="width:15%; text-align:center;">{{ $hour['date'] }}</td>
                                <td style="width:5%; text-align:center;">{{ $hour['totalhr'] }}</td>
                            </tr>
                            @php $isFirstRow = false; @endphp
                            @endforeach

                            <tr class="no-page-break">
                                <td style="width:7%;"></td>
                                <td style="width:25%;"></td>
                                <td style="width:13%;"></td>
                                <td style="width:10%;"></td>
                                <td colspan="2" style="text-align:right;"><b>Total Hours</b></td>
                                <td style="width:5%; text-align:center;"><b>{{ $task['total_hours'] }}</b></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
