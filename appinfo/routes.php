<?php

declare(strict_types=1);

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'run#list', 'url' => '/api/runs', 'verb' => 'GET'],
        ['name' => 'run#create', 'url' => '/api/runs', 'verb' => 'POST'],
        ['name' => 'run#addModule', 'url' => '/api/runs/{id}/modules', 'verb' => 'POST'],
        ['name' => 'run#publish', 'url' => '/api/runs/{id}/publish', 'verb' => 'POST'],
        ['name' => 'run#settings', 'url' => '/api/settings', 'verb' => 'GET'],
        ['name' => 'run#updateSettings', 'url' => '/api/settings', 'verb' => 'PUT'],
    ],
];
