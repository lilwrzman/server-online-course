<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Verification Success</title>
</head>
<body>
    <div>
        Hello, {{ $user->info['fullname'] }}! <br>
        Thanks for verify your email. <br>
        Ready to begin? <br>
        <a href="http://localhost:5173?show=login">Login Now</a>
    </div>
</body>
</html>
