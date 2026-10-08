<?php

declare(strict_types=1);

$appRoot = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($appRoot): void {
    $mappings = [
        'OCA\\FlzDataProtection\\' => $appRoot . '/tests/stubs/FlzDataProtection/',
        'OCA\\FlzBqPlanning\\' => $appRoot . '/lib/',
        'FlzBqPlanning\\Tests\\' => $appRoot . '/tests/',
    ];

    foreach ($mappings as $prefix => $root) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $path = $root . $relative . '.php';
        if (is_file($path)) {
            require_once $path;
        }
        return;
    }
});
