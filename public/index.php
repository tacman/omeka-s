<?php

use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/config/bootstrap.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($path) {
    $projectRoot = realpath(dirname(__DIR__));
    $assetPath = realpath(dirname(__DIR__) . $path);

    $isLegacyAsset = false;
    if (str_starts_with($path, '/application/asset/')) {
        $assetRoot = realpath($projectRoot . '/application/asset');
        $isLegacyAsset = $assetRoot && $assetPath && str_starts_with($assetPath, $assetRoot);
    } elseif (str_starts_with($path, '/themes/') || str_starts_with($path, '/modules/')) {
        $isLegacyAsset = str_contains($path, '/asset/');
    } elseif (str_starts_with($path, '/files/')) {
        $isLegacyAsset = true;
    }

    if ($projectRoot && $assetPath && $isLegacyAsset
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

$env = $_SERVER['APP_ENV'] ?? 'dev';
$debug = (bool) ($_SERVER['APP_DEBUG'] ?? ('prod' !== $env));

if ($debug) {
    umask(0000);
    Debug::enable();
}

$kernel = new Kernel($env, $debug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);

if ($response->getStatusCode() !== 404) {
    $response->send();
    $kernel->terminate($request, $response);
    return;
}

require dirname(__DIR__).'/index.php';
