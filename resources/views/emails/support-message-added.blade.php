<!Doctype HTML>
<html>
<head>
    <title>Email</title>
</head>
<body>
    <table>
        <thead>
            <tr>
                <td><img src="{{ asset('assets/img/image001.jpg') }}" alt="logo" width="73px" height="14px" /></td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="3" align="left">
                    This is an automatic mail from Seer Dynamics Portal. Kindly login to
                    <a href="{{ $ViewTicketURL ?? route('tasks.index') }}" style="font-family: calibri;font-size:16px;cursor:pointer;text-decoration:underline;">Seer Dynamics Support Portal</a> to reply
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <a style="font-family: calibri;font-size:16px;cursor:pointer;">{!! $message ?? '' !!}</a>
                </td>
            </tr>
            <tr style="display:{{ $IsNone ?? 'none' }}">
                <td colspan="3">
                    <img src="{{ asset('assets/img/attachment_icon.png') }}" alt="Attachment" style="position:relative;top:4px;height:20px;">
                    <span style="font-family: calibri;font-size:16px;cursor:pointer;">{{ $attachmment ?? '' }}</span>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <p style="font-family: calibri;font-size:13px;">Working Hours: 8:30 AM - 5:30 PM (Sun-Thu) GST|
                        <a href="http://www.timebie.com/std/gmt.php" style="font-size: 13px;font-family: calibri;color: #138d7d;text-decoration: underline;cursor: pointer;">Local Time</a>
                    </p>
                    <p style="font-family: calibri;font-size:13px;">The above is an email for a support case from Seer Dynamic
                        <a href="https://www.seerdynamics.com/" style="font-size: 13px;font-family: calibri;color: #138d7d;text-decoration: underline;cursor: pointer;">(www.seerdynamics.com).</a>
                    </p>
                    <p style="font-family: calibri;font-size:13px;">Thank you.</p>
                    <p style="font-family: calibri;font-size:13px;">Notification sent from
                        <a href="{{ url('/') }}" style="font-size: 13px;font-family: calibri;color: #138d7d;text-decoration: underline;cursor: pointer;">Seer Dynamics Support Portal.</a>
                    </p>
                </td>
            </tr>
            {{ htmlspecialchars($SupportMessages ?? '') }}
        </tbody>
    </table>
</body>
</html>


