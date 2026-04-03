<!Doctype HTML>
<html>
<head>
    <title>Email</title>
</head>
<body>
    <table style="width:100%;">
        <thead>
            <tr>
                <td><img src="{{ asset('assets/img/image001.jpg') }}" height="80px" width="300px" alt="logo" /></td>
            </tr>
            <tr>
                <td>
                    This is an automatic mail from Seer Dynamics Support Portal.
                    <br /> Kindly login to
                    <a href="{{ $ViewTicketURL ?? route('tasks.index') }}" style="font-family: calibri;font-size:16px;cursor:pointer;text-decoration:underline;">Seer Dynamics Support Portal</a> to reply
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="3">
                    <p style="font-family: calibri;font-size:20px;font-weight:bold;">{{ htmlspecialchars($title ?? '') }}</p>
                </td>
            </tr>
            <tr>
                <tr>
                    <td colspan="3">
                        <span style="font-size:13px;font-family:calibri;font-weight:bold;">Status:</span>
                        <span style="font-size:13px;font-family:calibri;color:#a7a5a5;">Created</span>
                        <span style="font-size:13px;font-family:calibri;color:#a7a5a5;"> > </span>
                        <span style="font-size:13px;font-family:calibri;color:#a7a5a5;">Attended</span>
                        <span style="font-size:13px;font-family:calibri;color:#a7a5a5;"> > </span>
                        <span style="font-size: 13px;font-family: calibri;color: #a7a5a5;">In process{{ htmlspecialchars($IsEtimate ?? '') }}{{ htmlspecialchars($IsAssigned ?? '') }}</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="background: #e8f3f4;padding: 30px 50px;font-family: calibri;">
                        <table style="background:#fff;width: 56%;margin: 0 auto;">
                            <tbody>
                                <tr>
                                    <td style="font-weight:bold;padding:10px 20px;">Created By</td>
                                    <td>{{ htmlspecialchars($CreatedBy ?? '') }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold;padding:10px 20px;">Project ID</td>
                                    <td>{{ htmlspecialchars($ProjectID ?? '') }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold;padding:10px 20px;">Ticket Number</td>
                                    <td>#{{ str_pad($task_id ?? 0, 5, '0', STR_PAD_LEFT) }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold;padding:10px 20px;">Assigned To</td>
                                    <td>{{ htmlspecialchars($AssignedTo ?? '') }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold;padding:10px 20px;">Estimated Days</td>
                                    <td>{{ htmlspecialchars($EstimatedDays ?? '') }}</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold;padding:10px 20px;">Estimated Hours</td>
                                    <td>{{ htmlspecialchars($EstimatedHours ?? '') }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <table style="text-align:center;width:100%;">
                            <tbody>
                                <tr>
                                    <td style="text-align:center;padding-top: 15px !important;">
                                        <a href="{{ $AcceptURL ?? $ViewTicketURL ?? route('tasks.index') }}" style="padding: 5px 10px;background: #6600FF;color: #fff;text-decoration: none;">Accept</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table style="text-align:center;width:100%;">
                            <tbody>
                                <tr>
                                    <td style="text-align:center;padding-top: 15px !important;">
                                        <a href="{{ $ViewTicketURL ?? route('tasks.index') }}" style="padding:5px 10px;background:#138d7d;color:#fff;text-decoration:none;">View ticket</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="3">
                        <p style="font-family: calibri;font-size:13px;">Working Hours: 8:30 AM - 5:30 PM (Sun-Thu) GST|
                            <a href="http://www.timebie.com/std/gmt.php" style="font-size:13px;font-family:calibri;color:#138d7d;text-decoration:underline;cursor:pointer;">Local Time</a>
                        </p>
                        <p style="font-family: calibri;font-size:13px;">The above is an email for a support case from Seer Dynamics
                            <a href="http://www.seerdynamics.com/" style="font-size:13px;font-family:calibri;color:#138d7d;text-decoration:underline;cursor:pointer;">(www.Seer Dynamics.com).</a>
                        </p>
                        <p style="font-family: calibri;font-size:13px;">Thank you.</p>
                        <p style="font-family: calibri;font-size:13px;">Notification sent from
                            <a href="{{ url('/') }}" style="font-size:13px;font-family:calibri;color:#138d7d;text-decoration:underline;cursor:pointer;">Seer Dynamics Support Portal.</a>
                        </p>
                    </td>
                </tr>
                {{ htmlspecialchars($SupportMessages ?? '') }}
        </tbody>
    </table>
</body>
</html>


