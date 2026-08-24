<?php

declare(strict_types=1);

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'run#list', 'url' => '/api/runs', 'verb' => 'GET'],
        ['name' => 'run#create', 'url' => '/api/runs', 'verb' => 'POST'],
        ['name' => 'run#addModule', 'url' => '/api/runs/{id}/modules', 'verb' => 'POST'],
        ['name' => 'run#updateModule', 'url' => '/api/runs/{id}/modules/{moduleId}', 'verb' => 'PUT'],
        ['name' => 'run#publish', 'url' => '/api/runs/{id}/publish', 'verb' => 'POST'],
        ['name' => 'run#settings', 'url' => '/api/settings', 'verb' => 'GET'],
        ['name' => 'run#updateSettings', 'url' => '/api/settings', 'verb' => 'PUT'],
        ['name' => 'proposal#suggest', 'url' => '/api/proposals', 'verb' => 'GET'],
        ['name' => 'proposal#suggestYear', 'url' => '/api/proposals/year', 'verb' => 'GET'],
        ['name' => 'teaching#lecturers', 'url' => '/api/lecturers', 'verb' => 'GET'],
        ['name' => 'teaching#createLecturer', 'url' => '/api/lecturers', 'verb' => 'POST'],
        ['name' => 'teaching#requests', 'url' => '/api/teaching-requests', 'verb' => 'GET'],
        ['name' => 'teaching#setLead', 'url' => '/api/runs/{id}/lead-lecturer', 'verb' => 'POST'],
        ['name' => 'teaching#createRequest', 'url' => '/api/modules/{id}/teaching-requests', 'verb' => 'POST'],
        ['name' => 'teaching#transitionRequest', 'url' => '/api/teaching-requests/{id}/transition', 'verb' => 'POST'],
    ],
];
