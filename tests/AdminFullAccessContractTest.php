<?php

declare(strict_types=1);

namespace AdBqPlanning\Tests;

TestRunner::test('admin full access has a bounded auditable app-local contract', static function (): void {
    $root=dirname(__DIR__);$template=(string)file_get_contents($root.'/templates/admin.php');$script=(string)file_get_contents($root.'/js/admin.js');$routes=(string)file_get_contents($root.'/appinfo/routes.php');$migration=(string)file_get_contents($root.'/lib/Migration/Version000003Date202608250001.php');
    foreach(['adbq-full-access-form','adbq-full-access-enabled','adbq-full-access-history','value="1440"'] as $value)assertTrue(str_contains($template,$value),'Adminfreigabe-UI fehlt: '.$value);
    foreach(['/api/admin/full-access','durationMinutes','targetUid','Widerrufen'] as $value)assertTrue(str_contains($script.$routes,$value),'Adminfreigabe-API fehlt: '.$value);
    foreach(['adbq_admin_access','target_uid','granted_by','starts_at','ends_at','revoked_at','revoked_by','created_at'] as $value)assertTrue(str_contains($migration,$value),'Adminfreigabe-Migration fehlt: '.$value);
    assertTrue(str_contains((string)file_get_contents($root.'/appinfo/info.xml'),'<admin>OCA\\AdBqPlanning\\Settings\\Admin</admin>'));
});
