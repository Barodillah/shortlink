<?php
declare(strict_types=1);

$rootConfigFile = dirname(__DIR__, 2) . '/config/config.php';
if (!file_exists($rootConfigFile)) {
    $rootConfigFile = dirname(__DIR__, 2) . '/config/config.example.php';
}
$rootConfig = require $rootConfigFile;

return $rootConfig['app'];

