<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Shaw Academy</title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- Leave those next 4 lines if you care about users using IE8 -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>



<div class="container text-center">
        <div class="jumbotron">
            <h3>Hypothetical Phoenix Authenticated Page</h3>
            <p>A user is logged into members area. Let's demonstrate SSO with Flarum.</p>
        </div>

        <a class="btn btn-primary btn-lg btn-block" href="/auth.php?auth_token=test">Visit Forum</a>
        <pre>href="/auth.php?auth_token=test"</pre>
</div>



<!-- Including Bootstrap JS (with its jQuery dependency) so that dynamic components work -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Faker/3.1.0/faker.min.js"></script>
<script>
    var randomUsername = faker.internet.userName();
    var randomAvatar = faker.internet.avatar();
    var randomEmail = faker.internet.email();
    var randomName = faker.name.findName();
    var fake = {
        username: randomUsername,
        avatarUrl: randomAvatar,
        email: randomEmail,
        name: randomName
    };
    var encoded = window.btoa(JSON.stringify(fake));
    console.log(fake);
    console.log(encoded);
</script>

</body>
</html>