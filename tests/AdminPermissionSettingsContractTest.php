<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

TestRunner::test('BQ permission mapping lives only in the Nextcloud admin section', static function (): void {
    $root = dirname(__DIR__);
    $admin = (string)file_get_contents($root . '/templates/admin.php');
    $workspace = (string)file_get_contents($root . '/templates/index.php');
    $settings = (string)file_get_contents($root . '/lib/Settings/Admin.php');

    foreach (['planningRoles[]', 'teachingRoles[]', 'publishingRoles[]', 'pfkPoolRoles[]'] as $field) {
        assertTrue(str_contains($admin, $field), 'Admin-Berechtigungsfeld fehlt: ' . $field);
        assertTrue(!str_contains($workspace, $field), 'Admin-Berechtigungsfeld liegt noch im Nutzungsbereich: ' . $field);
    }
    assertTrue(str_contains($admin, 'Stellvertretende Pflegedienstleitung'));
    assertTrue(str_contains($admin, 'Keine der Rollen erhält automatisch eine andere Berechtigung.'));
    assertTrue(str_contains($settings, 'RoleSettingsService'));
    assertTrue(str_contains($settings, 'AuthorizationService'));
    assertTrue(str_contains($admin, "script(Application::APP_ID, 'admin-settings')"));
});
