<!DOCTYPE html>
<html>

<head>
    <title>Email Confirmation</title>
</head>

<body>
    <h1>Hello {{$user->name}} </h1>
    <p>Password reset link. </p>
    <a href="http://localhost:8001/api/reset-password/{{$confirm_code}}"> Please Click Here</a>
</body>

</html>
