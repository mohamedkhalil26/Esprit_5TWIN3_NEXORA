<?php

use Illuminate\Http\Request;

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/uploads/reclamations/') || str_contains($path, '..')) {
    http_response_code(403);
    exit;
}
$root = dirname(__DIR__, 2);
if (str_starts_with($path, '/storage/')) {
    $photo = $root.'/storage/app/private/recette-finale/runtime/app/public/'.substr($path, 9);
    if (is_file($photo)) {
        header('Content-Type: '.mime_content_type($photo));
        readfile($photo);
        exit;
    }
    http_response_code(404);
    exit;
}
if (is_file($root.'/public'.$path) && $path !== '/index.php') {
    return false;
}
define('LARAVEL_START', microtime(true));
$app = require __DIR__.'/bootstrap.php';
$app->handleRequest(Request::capture());
