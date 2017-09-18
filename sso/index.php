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

    <style type="text/css">
        /* Profile container */
        .profile {
            margin: 20px 0;
        }

        /* Profile sidebar */
        .profile-sidebar {
            padding: 20px 0 10px 0;
            background: #fff;
        }

        .profile-userpic img {
            float: none;
            margin: 0 auto;
            width: 50%;
            height: 50%;
            -webkit-border-radius: 50% !important;
            -moz-border-radius: 50% !important;
            border-radius: 50% !important;
        }

        .profile-usertitle {
            text-align: center;
            margin-top: 20px;
        }

        .profile-usertitle-name {
            color: #5a7391;
            font-size: 30px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .profile-usertitle-job {
            text-transform: uppercase;
            color: #5b9bd1;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .profile-userbuttons {
            text-align: center;
            margin-top: 10px;
        }

        .profile-userbuttons .btn {
            text-transform: uppercase;
            font-size: 1816;
            font-weight: 600;
            padding: 6px 15px;
            margin-right: 5px;
        }

        .profile-userbuttons .btn:last-child {
            margin-right: 0px;
        }

        .profile-usermenu {
            margin-top: 30px;
        }

        .profile-usermenu ul li {
            border-bottom: 1px solid #f0f4f7;
        }

        .profile-usermenu ul li:last-child {
            border-bottom: none;
        }

        .profile-usermenu ul li a {
            color: #93a3b5;
            font-size: 18px;
            font-weight: 400;
        }

        .profile-usermenu ul li a i {
            margin-right: 8px;
            font-size: 14px;
        }

        .profile-usermenu ul li a:hover {
            background-color: #fafcfd;
            color: #5b9bd1;
        }

        .profile-usermenu ul li.active {
            border-bottom: none;
        }

        .profile-usermenu ul li.active a {
            color: #5b9bd1;
            background-color: #f6f9fb;
            border-left: 2px solid #5b9bd1;
            margin-left: -2px;
        }

        /* Profile Content */
        .profile-content {
            padding: 20px;
            background: #fff;
            min-height: 460px;
        }
    </style>
</head>
<body>



<div class="container text-center">
        <div class="jumbotron">
            <h3>Hypothetical Phoenix Authenticated Page</h3>
            <p>A user is logged into members area. Let's demonstrate SSO with Flarum.</p>
        </div>

        <div class="row">
            <div class="col-md-12">
                <p class="lead">Fake data generated using <strong>faker.js</strong></p>

                <div class="row profile">
                    <div class="col-md-4 col-md-offset-4">
                        <div class="profile-sidebar">

                            <div class="profile-userpic">
                                <img class="img-responsive" alt="">
                            </div>

                            <div class="profile-usertitle">
                                <div class="profile-usertitle-name"></div>
                                <div class="profile-usertitle-job"> Developer </div>

                            </div>

                            <div class="profile-userbuttons">
                                <button type="button" class="email btn btn-success btn-sm"></button>
                            </div>
                            <div class="profile-userbuttons">
                                <button type="button" class="username btn btn-danger btn-sm"></button>
                            </div>


                            <div class="profile-usermenu">
                                <ul class="nav">
                                    <li class="active">
                                        <a class="visit-link" href="">
                                            <i class="glyphicon glyphicon-list"></i>
                                            Visit Forum </a>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
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
        dotusername: randomUsername,
        username: randomUsername.replace('.',''),
        avatarUrl: randomAvatar,
        email: randomEmail,
        name: randomName
    };
    var encoded = window.btoa(JSON.stringify(fake));

    console.log(fake);
    console.log(encoded);

    var targetUrl = "&target_url=/t/parent-tag";
    var authRedirect = "/auth.php?auth_token=" + encoded + targetUrl;
    $('.visit-link').attr("href", authRedirect);

    $('.profile-userpic .img-responsive').attr("src", randomAvatar);
    $('.profile-usertitle-name').html(randomName);
    $('.container pre').html(authRedirect);
    $('.username').html(randomUsername);
    $('.email').html(randomEmail);

</script>

</body>
</html>