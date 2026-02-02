<?php
define('OMEKA_PATH', __DIR__);
chdir(OMEKA_PATH);
date_default_timezone_set('UTC');

require 'vendor/autoload.php';

if (!interface_exists(\Doctrine\ORM\Proxy\Proxy::class)
    && interface_exists(\Doctrine\Persistence\Proxy::class)
) {
    class_alias(\Doctrine\Persistence\Proxy::class, \Doctrine\ORM\Proxy\Proxy::class);
}
