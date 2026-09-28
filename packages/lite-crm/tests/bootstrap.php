<?php

declare(strict_types=1);

/*
 * Use the package's own vendor directory when it is installed on its own,
 * and the host's when it runs inside the monorepo.
 */
$autoloaders = [
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../vendor/autoload.php',
];

foreach ($autoloaders as $autoloader) {
    if (is_file($autoloader)) {
        require $autoloader;

        return;
    }
}

throw new RuntimeException('Run "composer install" in the package or the host application first.');
