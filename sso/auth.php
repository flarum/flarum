<?php

require_once('SSOController.php');

$authToken = $_GET['auth_token'];
$targetUrl = (isset($_GET['target_url'])) ? $_GET['target_url'] : null;
if (!isset($authToken) || empty($authToken) || $authToken == "") echo 'Login failed';
$decodedTestData = json_decode(base64_decode($authToken));

$email = $decodedTestData->email;
$username = $decodedTestData->username;
$avatarUrl = $decodedTestData->avatarUrl;

$forum = new SSOController();
$forum->login($username, $email, $avatarUrl);
$forum->redirectToForum($targetUrl);
