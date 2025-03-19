<?php
// PHP Development Server Router Script

// Get the requested URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Route API requests to api.php
if (strpos($uri, '/api/') === 0) {
    require_once __DIR__ . '/public/api.php';
    return;
}

// Handle static files
$static_extensions = ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf', 'eot'];
$path_parts = pathinfo($uri);
if (isset($path_parts['extension']) && in_array(strtolower($path_parts['extension']), $static_extensions)) {
    $file = __DIR__ . '/public' . $uri;
    if (file_exists($file)) {
        // Set appropriate content type
        switch (strtolower($path_parts['extension'])) {
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            case 'jpg':
            case 'jpeg':
                header('Content-Type: image/jpeg');
                break;
            case 'png':
                header('Content-Type: image/png');
                break;
            case 'gif':
                header('Content-Type: image/gif');
                break;
            case 'svg':
                header('Content-Type: image/svg+xml');
                break;
            case 'woff':
                header('Content-Type: font/woff');
                break;
            case 'woff2':
                header('Content-Type: font/woff2');
                break;
            case 'ttf':
                header('Content-Type: font/ttf');
                break;
            case 'eot':
                header('Content-Type: application/vnd.ms-fontobject');
                break;
        }
        readfile($file);
        return;
    }
}

// Default to index.php for all other requests
require_once __DIR__ . '/public/index.php';
