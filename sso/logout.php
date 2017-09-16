<?php

require_once('SSOController.php');

$forum = new SSOController();

$forum->logout();
$forum->redirectToForum();
