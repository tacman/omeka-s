<?php

$reportDeprecated = getenv('OMEKA_REPORT_DEPRECATED') === '1';
error_reporting($reportDeprecated ? E_ALL : (E_ALL & ~E_DEPRECATED));

$displayErrors = getenv('OMEKA_DISPLAY_ERRORS') === '1'
    || getenv('APPLICATION_ENV') === 'development'
    || getenv('REDIRECT_APPLICATION_ENV') === 'development';
ini_set('display_errors', $displayErrors ? '1' : '0');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($path !== null && $path !== '/') {
    $file = __DIR__ . $path;
    if (is_file($file)) {
        return false;
    }
}

require __DIR__ . '/index.php';
