<?php
/**
 * PHP Built-in Web Server Router
 * Usage: php -S localhost:8080 router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route /api/* to api/index.php
if (preg_match('#^/api(/.*)?$#', $uri)) {
    require __DIR__ . '/api/index.php';
    exit;
}

// Serve existing static files directly
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false; // Built-in server serves file directly
}

// Default index
if ($uri === '/' || $uri === '') {
    require __DIR__ . '/index.php';
    exit;
}

// If direct PHP file requested
if (file_exists($filePath) && is_file($filePath)) {
    require $filePath;
    exit;
}

// 404 handler
http_response_code(404);
require __DIR__ . '/404.php';
