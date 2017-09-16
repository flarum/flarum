<?php

require_once __DIR__ . '/Forum.php';

$forum = new Forum();

$forum->logout();

if ($_GET['forum']) {
    $forum->redirectToForum();
}
