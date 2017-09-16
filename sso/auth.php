<?php

use SSO\Flarum;

$auth_token = $_GET['auth_token'];


if (isset($users[$username]) && $users[$username]['password'] === $password) {
    $email = $users[$username]['email'];
    $forum = new Forum();
    $forum->login($username, $email);
    $forum->redirectToForum();
} elseif (!empty($username) || !empty($password)) {
    echo 'Login failed';
}