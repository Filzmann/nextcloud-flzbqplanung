<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

TestRunner::test('admin full access has a bounded auditable app-local contract', static function (): void {
    $root=dirname(__DIR__);$adminTemplate=(string)file_get_contents($root.'/templates/admin.php');$appTemplate=(string)file_get_contents($root.'/templates/index.php');$script=is_file($root.'/js/admin-access.js')?(string)file_get_contents($root.'/js/admin-access.js'):'';$routes=(string)file_get_contents($root.'/appinfo/routes.php');$migration=(string)file_get_contents($root.'/lib/Migration/Version000003Date202608250001.php');
    foreach(['flz-bq-full-access-form','flz-bq-full-access-enabled','flz-bq-full-access-history','value="1440"'] as $value){assertTrue(str_contains($appTemplate,$value),'Rollenabhängige Fachapp-Steuerung fehlt: '.$value);assertTrue(!str_contains($adminTemplate,$value),'Freigabesteuerung liegt noch im technischen Adminbereich: '.$value);}
    foreach(['/api/admin/full-access','durationMinutes','targetUid','Widerrufen'] as $value)assertTrue(str_contains($script.$routes,$value),'Adminfreigabe-API fehlt: '.$value);
    foreach(['canManageAdminAccess','showMissingAdminGrant','showAdminAccessLink','hasBqAccess'] as $value)assertTrue(str_contains($appTemplate.(string)file_get_contents($root.'/lib/Controller/PageController.php'),$value),'Sichere Rollenprojektion fehlt: '.$value);
    assertTrue(str_contains((string)file_get_contents($root.'/lib/Controller/PageController.php'),'$canManageAdminAccess && $showMissingAdminGrant'),'Direktlink ist nicht auf dasselbe kombinierte Admin- und Datenschutzkonto begrenzt.');
    foreach(['flz_bq_admin_access','target_uid','granted_by','starts_at','ends_at','revoked_at','revoked_by','created_at'] as $value)assertTrue(str_contains($migration,$value),'Adminfreigabe-Migration fehlt: '.$value);
    assertTrue(str_contains((string)file_get_contents($root.'/appinfo/info.xml'),'<admin>OCA\\FlzBqPlanning\\Settings\\Admin</admin>'));
});
