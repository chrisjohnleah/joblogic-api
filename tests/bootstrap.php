<?php

$autoloaders = [
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../vendor/autoload.php',
];

foreach ($autoloaders as $autoloader) {
    if (is_file($autoloader)) {
        require_once $autoloader;

        return;
    }
}

throw new RuntimeException('Composer autoload.php was not found. Run composer install before testing the package.');
