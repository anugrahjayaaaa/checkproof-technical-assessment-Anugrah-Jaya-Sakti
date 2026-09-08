<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New User Registered</title>
</head>
<body>
    <h1>New User Registered</h1>

    <p>A new user has been created.</p>

    <p>
        <strong>Name:</strong> {{ $user->name }}
    </p>

    <p>
        <strong>Email:</strong> {{ $user->email }}
    </p>

    <p>
        <strong>Created At:</strong> {{ $user->created_at }}
    </p>
</body>
</html>
