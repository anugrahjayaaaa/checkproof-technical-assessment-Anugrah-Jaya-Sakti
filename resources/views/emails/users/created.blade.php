<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Created</title>
</head>
<body>
    <h1>Welcome, {{ $user->name }}!</h1>

    <p>Your account has been successfully created.</p>

    <p>
        <strong>Email:</strong> {{ $user->email }}
    </p>
    <p>
        <strong>Password:</strong> {{ $password }}
    </p>

    <p>Thank you.</p>
</body>
</html>
