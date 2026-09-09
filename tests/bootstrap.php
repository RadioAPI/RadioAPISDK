<?php

declare(strict_types=1);

$packageAutoload = __DIR__.'/../vendor/autoload.php';
$applicationAutoload = __DIR__.'/../../vendor/autoload.php';

require is_file($packageAutoload) ? $packageAutoload : $applicationAutoload;

spl_autoload_register(static function (string $class): void {
    $testPrefix = 'RadioApi\\Tests\\';

    if (str_starts_with($class, $testPrefix)) {
        $path = __DIR__.'/'.str_replace('\\', '/', substr($class, strlen($testPrefix))).'.php';

        if (is_file($path)) {
            require $path;
        }

        return;
    }

    $prefix = 'RadioApi\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $path = __DIR__.'/../src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

    if (is_file($path)) {
        require $path;
    }
});
