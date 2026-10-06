<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Reset your administrator password</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <p>Hello {{ $admin->full_name }},</p>

    <p>We received a request to reset the password of your administrator account.</p>

    <p>
        <a href="{{ $resetUrl }}">Reset password</a>
    </p>

    <p>This link expires in {{ $expiresInMinutes }} minutes and can be used only once.</p>

    <p>If you did not request this, you can safely ignore this e-mail &mdash; your password will not change.</p>
</body>
</html>
