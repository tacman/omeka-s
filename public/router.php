<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($path !== null && $path !== '/') {
    $publicFile = __DIR__ . $path;
    if (is_file($publicFile)) {
        return false;
    }
}

$projectRoot = realpath(dirname(__DIR__));
if ($path && $projectRoot) {
    $assetPath = realpath($projectRoot . $path);
    $isLegacyAsset = false;

    if (str_starts_with($path, '/application/asset/')) {
        $assetRoot = realpath($projectRoot . '/application/asset');
        $isLegacyAsset = $assetRoot && $assetPath && str_starts_with($assetPath, $assetRoot);
    } elseif (str_starts_with($path, '/themes/') || str_starts_with($path, '/modules/')) {
        $isLegacyAsset = str_contains($path, '/asset/');
    } elseif (str_starts_with($path, '/files/')) {
        $isLegacyAsset = true;
    }

    if ($assetPath && $isLegacyAsset
        && str_starts_with($assetPath, $projectRoot) && is_file($assetPath)
    ) {
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($assetPath);
            if (is_string($mime)) {
                header('Content-Type: ' . $mime);
            }
        }
        header('Content-Length: ' . filesize($assetPath));
        readfile($assetPath);
        return;
    }
}

require __DIR__ . '/index.php';
