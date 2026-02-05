<?php
namespace Omeka;

$reader = new \Laminas\Config\Reader\Ini;

$envValue = function (string $key) {
  return $_SERVER[$key]
    ?? $_ENV[$key]
    ?? getenv($key);
};

$url = $envValue('OMEKA_DB_CONNECTION_URL');
if (!$url) {
  $url = $envValue('DATABASE_URL');
}
if ($url) {
  $appEnv = $envValue('APP_ENV') ?: $envValue('APPLICATION_ENV') ?: 'dev';
  $url = str_replace(['%kernel.project_dir%', '%kernel.environment%'], [OMEKA_PATH, $appEnv], $url);
}

try {
  $database = $reader->fromFile(OMEKA_PATH . '/config/database.ini');
} catch (\Laminas\Config\Exception\RuntimeException $e) {
  if (!$url) {
    throw $e;
  }
} finally {
}

$envOverrides = [
  'OMEKA_DB_DRIVER' => 'driver',
  'OMEKA_DB_HOST' => 'host',
  'OMEKA_DB_PORT' => 'port',
  'OMEKA_DB_NAME' => 'dbname',
  'OMEKA_DB_USER' => 'user',
  'OMEKA_DB_PASSWORD' => 'password',
  'OMEKA_DB_UNIX_SOCKET' => 'unix_socket',
  'OMEKA_DB_LOG_PATH' => 'log_path',
  'OMEKA_DB_CHARSET' => 'charset',
];

if (!$url) {
  foreach ($envOverrides as $envKey => $configKey) {
    $value = $envValue($envKey);
    if ($value !== false && $value !== '') {
      $database[$configKey] = $value;
    }
  }
} else {
  $parser = new \Doctrine\DBAL\Tools\DsnParser([
    'sqlite' => 'pdo_sqlite',
    'sqlite3' => 'pdo_sqlite',
  ]);
  $parsed = $parser->parse($url);
  $logPath = $envValue('OMEKA_DB_LOG_PATH') ?: ($database['log_path'] ?? null);
  $database = $parsed;
  $database['url'] = $url;
  if ($logPath) {
    $database['log_path'] = $logPath;
  }
}

return [
    'modules' => [
        'Laminas\Form',
        'Laminas\I18n',
        'Laminas\Mvc\I18n',
        'Laminas\Navigation',
        'Laminas\Router',
        'Omeka',
    ],
    'module_listener_options' => [
        'module_paths' => [
            'Omeka' => OMEKA_PATH . '/application',
            OMEKA_PATH . '/modules',
        ],
        'config_glob_paths' => [
            OMEKA_PATH . '/config/local.config.php',
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Omeka\Connection' => Service\ConnectionFactory::class,
            'Omeka\ModuleManager' => Service\ModuleManagerFactory::class,
            'Omeka\Status' => Service\StatusFactory::class,
        ],
    ],
    'connection' => $database,
];
