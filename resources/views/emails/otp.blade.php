<!DOCTYPE html>
<html>
<head>
    <title>OTP Verification</title>
</head>
<body>
    <h2>OTP Verification Code</h2>
    <p>Hello {{ $user->name }},</p>
    <p>Your OTP verification code is: <strong>{{ $otp }}</strong></p>
    <p>This code will expire in 10 minutes.</p>
    <p>If you didn't request this code, please ignore this email.</p>
    <br>
    <p>Thank you,<br>{{ config('app.name') }}</p>
</body>
</html>