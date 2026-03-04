<!Doctype HTML>
<html>
<head>
    <title>Seer Dynamics</title>
</head>
<body>
    <table style="font-family: calibri;width:100%;">
        <tbody>
            <tr>
                <td style="padding: 40px;background: #e8f3f4;padding-top:20px;">
                    <table style="font-family: calibri;width:100%;">
                        <thead>
                            <tr>
                                <td style="padding-bottom:20px;">
                                    <img src="{{ asset('assets/img/image001.jpg') }}" alt="logo" width="40px" height="10px" />
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td style="padding: 20px;background: #fff;border-radius: 29px;">
                                    <table style="font-family: calibri;width:100%;">
                                        <tbody>
                                            <tr>
                                                <td colspan="3" style="border-bottom: 2px solid;padding-bottom: 30px;font-size:18px;">
                                                    This is an automatic mail from Seer Dynamics Support Portal.
                                                    <div>
                                                        Kindly login to
                                                        <a href="{{ url('projects/tasks') }}" style="font-family: calibri;color:#138d7d;font-size:18px;cursor:pointer;text-decoration:underline;">
                                                            Seer Dynamics Support Portal
                                                        </a>
                                                        to reply
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="3">
                                                    <p style="font-family: calibri;font-size:20px;font-weight:bold;">{{ htmlspecialchars($title ?? '') }}</p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="3">
                                                    <span style="font-size:13px;font-family:calibri;font-weight:bold;">Status:</span>
                                                    <span style="font-size:13px;font-family:calibri;color:#a7a5a5;"> > </span>
                                                    <span style="font-size:13px;font-family:calibri;color:#138d7d;">Created</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="3">
                                                    <table>
                                                        <tbody>
                                                            <tr>
                                                                <td style="font-weight:bold;">Created By</td>
                                                                <td style="padding:10px 20px;">{{ htmlspecialchars($CreatedBy ?? '') }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="font-weight:bold;">Project ID</td>
                                                                <td style="padding:10px 20px;">{{ htmlspecialchars($ProjectID ?? '') }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="font-weight:bold;">Ticket Number</td>
                                                                <td style="padding:10px 20px;">#{{ str_pad($task_id ?? 0, 5, '0', STR_PAD_LEFT) }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <table>
                                                        <tbody>
                                                            <tr>
                                                                <td style="text-align:center;padding-top: 15px !important;">
                                                                    <a href="{{ url('projects/tasks') }}" style="padding:5px 10px;background:#138d7d;color:#fff;text-decoration:none;">View ticket</a>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="3" style="font-family: Seogui;font-size:16px; padding: 40px;background: #e8f3f4;">
                                                    {{ htmlspecialchars($title ?? '') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    <p style="font-family: calibri;font-size:16px;">
                                        <div style="font-weight:bold;">Working Hours:</div>
                                        <div>8:30 AM - 5:30 PM (Sun-Thu) GST|
                                            <a href="http://www.timebie.com/std/gmt.php" style="font-size:16px;font-family:calibri;color:#138d7d;text-decoration:underline;cursor:pointer;">Local Time</a>
                                        </div>
                                    </p>
                                    <p style="font-family: calibri;font-size:16px;">
                                        The above is an email for a support case from Seer Dynamics
                                        <div><a href="https://www.seerdynamics.com/" style="font-size:16px;font-family:calibri;color:#138d7d;text-decoration:underline;cursor:pointer;">(www.seerdynamics.com).</a></div>
                                    </p>
                                    <p style="font-family: calibri;font-size:16px;font-weight:bold;">Thank you.</p>
                                    <p style="font-family: calibri;font-size:16px;">
                                        Notification sent from
                                        <a href="{{ url('/') }}" style="font-size:16px;font-family:calibri;color:#138d7d;text-decoration:underline;cursor:pointer;">Seer Dynamics Support Portal.</a>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
