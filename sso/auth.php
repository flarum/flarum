<?php

use SSO\Flarum;
use SSO\Forum;

$authToken = $_GET['auth_token'];
$decodedTestData = json_decode(base64_decode($auth_token));

$email = $decodedTestData['email'];
$username = $decodedTestData['username'];
$avatarUrl = $decodedTestData['avatarUrl'];

$forum = new Forum();
$forum->login($username, $email, $avatarUrl);
$forum->redirectToForum();
