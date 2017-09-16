<?php

require_once __DIR__ . '/Forum.php';

$users = [
    'user' => [
        'password' => 'password',
        'email' => 'test@mailinator.com',
    ],
];

$username = empty($_POST['username']) ? '' : $_POST['username'];
$password = empty($_POST['password']) ? '' : $_POST['password'];

if (isset($users[$username]) && $users[$username]['password'] === $password) {
    $email = $users[$username]['email'];
    $forum = new Forum();
    $forum->login($username, $email);
    $forum->redirectToForum();
} elseif (!empty($username) || !empty($password)) {
    echo 'Login failed';
}