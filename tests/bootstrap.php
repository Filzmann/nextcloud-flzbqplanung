<?php

declare(strict_types=1);

$appRoot = dirname(__DIR__);
$workspaceRoot = dirname($appRoot);

spl_autoload_register(static function (string $class) use ($appRoot, $workspaceRoot): void {
    $mappings = [
        'OCA\\FlzDataProtection\\' => $appRoot . '/tests/stubs/FlzDataProtection/',
        'OCA\\FlzRecruitment\\' => $workspaceRoot . '/flzrecruitment/lib/',
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
