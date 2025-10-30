<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f4f4f4;
            padding: 30px;
            border-radius: 10px;
        }
        .header {
            background-color: #10b981;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background-color: white;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reset Password</h1>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $nama }}</strong>,</p>
            <p>Kami menerima permintaan untuk mereset password akun Anda.</p>
            <p>Klik tombol di bawah ini untuk melanjutkan proses reset password:</p>
            
            <div style="text-align: center;">
                <a href="{{ env('FRONTEND_URL') }}/reset-password?token={{ $token }}&email={{ $email }}" class="button">
                    Reset Password
                </a>
            </div>
            
            <p>Atau copy link berikut ke browser Anda:</p>
            <p style="background-color: #f4f4f4; padding: 10px; border-radius: 5px; word-break: break-all;">
                {{ env('FRONTEND_URL') }}/reset-password?token={{ $token }}&email={{ $email }}
            </p>
            
            <p><strong>Catatan:</strong></p>
            <ul>
                <li>Link ini berlaku selama <strong>60 menit</strong></li>
                <li>Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini</li>
                <li>Password Anda tidak akan berubah sampai Anda mengklik link di atas dan membuat password baru</li>
            </ul>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>