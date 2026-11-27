<?php

declare(strict_types=1);

namespace FlzBqPlanning\Tests;

TestRunner::test('admin full access has a bounded auditable app-local contract', static function (): void {
    $root=dirname(__DIR__);$adminTemplate=(string)file_get_contents($root.'/templates/admin.php');$appTemplate=(string)file_get_contents($root.'/templates/index.php');$script=is_file($root.'/js/admin-access.js')?(string)file_get_contents($root.'/js/admin-access.js'):'';$bundleOverlay=is_file($root.'/js/shared/overlay.js')?(string)file_get_contents($root.'/js/shared/overlay.js'):'';$routes=(string)file_get_contents($root.'/appinfo/routes.php');$migration=(string)file_get_contents($root.'/lib/Migration/Version000003Date202608250001.php');
    foreach(['flz-bq-full-access-form','flz-bq-full-access-enabled','flz-bq-full-access-history','value="1440"'] as $value){assertTrue(str_contains($appTemplate,$value),'Rollenabhängige Fachapp-Steuerung fehlt: '.$value);assertTrue(!str_contains($adminTemplate,$value),'Freigabesteuerung liegt noch im technischen Adminbereich: '.$value);}
    foreach(['/api/admin/full-access','durationMinutes','targetUid'] as $value)assertTrue(str_contains($script.$routes,$value),'Adminfreigabe-API fehlt: '.$value);
    foreach(['canManageAdminAccess','showMissingAdminGrant','showAdminAccessLink','hasBqAccess'] as $value)assertTrue(str_contains($appTemplate.(string)file_get_contents($root.'/lib/Controller/PageController.php'),$value),'Sichere Rollenprojektion fehlt: '.$value);
    assertTrue(str_contains((string)file_get_contents($root.'/lib/Controller/PageController.php'),'$canManageAdminAccess && $showMissingAdminGrant'),'Direktlink ist nicht auf dasselbe kombinierte Admin- und Datenschutzkonto begrenzt.');
    foreach(['flz_bq_admin_access','target_uid','granted_by','starts_at','ends_at','revoked_at','revoked_by','created_at'] as $value)assertTrue(str_contains($migration,$value),'Adminfreigabe-Migration fehlt: '.$value);
    assertTrue(str_contains((string)file_get_contents($root.'/appinfo/info.xml'),'<admin>OCA\\FlzBqPlanning\\Settings\\Admin</admin>'));
    $overlayId='flz-bq-admin-access-overlay';
    foreach(['id="'.$overlayId.'"','role="dialog"','aria-modal="true"','flz-bq-admin-access-overlay-close','id="bq-admin-grant-warning"','aria-haspopup="dialog"','aria-controls="'.$overlayId.'"','aria-expanded'] as $value)assertTrue(str_contains($appTemplate,$value),'Overlay-Vertrag fehlt in der Vorlage: '.$value);
    assertTrue(!str_contains($appTemplate,'<details class="bq-admin-grant-warning"'),'Warn-Icon ist noch als aufklappbares details-Element umgesetzt.');
    assertTrue(!str_contains($appTemplate,'href="#flz-bq-full-access-heading"'),'Veralteter Anchor-Verweis auf die Freigabesteuerung ist noch vorhanden.');
    $overlayPos=strpos($appTemplate,'id="'.$overlayId.'"');$sectionPos=strpos($appTemplate,'flz-bq-full-access-form');$warningPos=strpos($appTemplate,'Kein fachlicher Admin-Vollzugriff.');
    assertTrue($overlayPos!==false&&$sectionPos!==false&&$warningPos!==false&&$overlayPos<$sectionPos&&$overlayPos<$warningPos,'Warnung und Freigabesteuerung liegen nicht innerhalb des Overlays.');
    assertTrue(str_contains($script,$overlayId)&&str_contains($script,'AdminAccessOverlay'),'Overlay-Steuerung fehlt im JS: '.$overlayId);
    foreach(['AdminAccessOverlay','Overlay','Escape','aria-expanded','data-open-admin-access','flz-shared-overlay','flz-shared-warn-trigger','Widerrufen','hidden'] as $value)assertTrue(str_contains($bundleOverlay,$value),'Geteiltes Overlay-Bundle fehlt: '.$value);
    foreach(["script(Application::APP_ID, 'shared/overlay')",'class="flz-shared-overlay"','class="flz-shared-warn-trigger"'] as $value)assertTrue(str_contains($appTemplate,$value),'Overlay-Vertrag fehlt in der Vorlage: '.$value);
});
