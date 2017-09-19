<?php
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);
$tokens = explode('/', $uri);
$handler = __DIR__.'/'.$tokens[1].'.php';
if ($uri !== '/' && file_exists(__DIR__.'/'.$uri)) {
    // If requesting the root page, or raw files/folders
    // Return as is
    return false;
} elseif (file_exists($handler)) {
    // This is for /api and /admin
    include_once $handler;
} else {
    include_once __DIR__.'/index.php';
}