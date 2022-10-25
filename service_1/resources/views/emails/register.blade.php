<!DOCTYPE html>
<html>

<head>
    <title>Email Confirmation</title>
</head>

<body>
    <h1>Hello {{$user->name}} </h1>
    <p>Please verify your email address</p>
    <a href="http://localhost:8001/api/email-verify/{{$confirm_code}}"> Please Click Here</a>
</body>

</html>
