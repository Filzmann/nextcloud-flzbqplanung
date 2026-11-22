<?php

declare(strict_types=1);

use OCA\FlzBqPlanning\AppInfo\Application;

script(Application::APP_ID, 'admin-settings');
style(Application::APP_ID, 'style');

$canConfigure = (bool)($_['canConfigurePermissions'] ?? false);
$settings = is_array($_['permissionSettings'] ?? null) ? $_['permissionSettings'] : [];
$selection = is_array($_['roleSelection'] ?? null) ? $_['roleSelection'] : [];
$options = is_array($selection['options'] ?? null) ? $selection['options'] : [];
$permissionSections = [
    'planningRoles[]' => ['planningRoles', 'Durchläufe planen', 'Plant Durchläufe und pflegt Curriculum-Snapshots.'],
    'teachingRoles[]' => ['teachingRoles', 'Lehre verwalten', 'Verwaltet Lehrzuordnungen sowie externe Dozentinnen und Anfragen.'],
    'publishingRoles[]' => ['publishingRoles', 'Durchläufe veröffentlichen', 'Gibt geprüfte und vollständige Durchläufe frei.'],
    'pfkPoolRoles[]' => ['pfkPoolRoles', 'Internen PFK-Pool verwalten', 'Nimmt ausschließlich Mitglieder der Organigrammrolle Pflegefachkräfte in den internen Pool auf.'],
];
?>
<section id="flz-bq-admin" class="section bq-card" aria-labelledby="flz-bq-admin-heading">
    <h2 id="flz-bq-admin-heading">Filzmann BQ-Planer</h2>
    <p>Hier ordnest du die vier fachlichen BQ-Berechtigungen den passenden Organigrammrollen zu. Gruppen werden nicht als Berechtigungen gespeichert; ihre aktuellen Nextcloud-IDs werden aus dem öffentlichen V1-Vertrag von flz_organization ermittelt.</p>
    <p><strong>Standard:</strong> Die Stellvertretende Pflegedienstleitung erhält alle vier Berechtigungen. Pflegedienstleitung und Pflegefachkräfte können je Berechtigung zusätzlich ausgewählt werden. Keine der Rollen erhält automatisch eine andere Berechtigung.</p>

    <?php if (!$canConfigure): ?>
        <p role="alert">Für Änderungen ist zusätzlich zur Nextcloud-Administration ein aktiver, zeitlich begrenzter BQ-Admin-Vollzugriff erforderlich.</p>
    <?php else: ?>
        <?php if (($selection['message'] ?? '') !== ''): ?>
            <p role="alert"><?php p((string)$selection['message']); ?></p>
        <?php endif; ?>
        <form id="flz-bq-permission-settings" class="bq-form">
            <?php foreach ($permissionSections as $fieldName => [$settingsKey, $label, $description]): ?>
                <?php $selectedRoles = is_array($settings[$settingsKey] ?? null) ? $settings[$settingsKey] : []; ?>
                <fieldset class="bq-card">
                    <legend><?php p($label); ?></legend>
                    <p><?php p($description); ?></p>
                    <?php foreach ($options as $option): ?>
                        <?php
                        $roleKey = (string)($option['roleKey'] ?? '');
                        $exists = (bool)($option['exists'] ?? false);
                        ?>
                        <label>
                            <input
                                type="checkbox"
                                name="<?php p($fieldName); ?>"
                                value="<?php p($roleKey); ?>"
                                <?php if (in_array($roleKey, $selectedRoles, true)): ?>checked<?php endif; ?>
                                <?php if (!$exists): ?>disabled<?php endif; ?>
                            >
                            <?php p((string)($option['label'] ?? $roleKey)); ?>
                            <small><?php p((string)($option['groupId'] ?? '')); ?><?php if (!$exists): ?> – Gruppe fehlt in Nextcloud; Berechtigung bleibt deaktiviert<?php endif; ?></small>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
            <?php endforeach; ?>
            <button type="submit" class="primary" <?php if (!($selection['available'] ?? false)): ?>disabled<?php endif; ?>>Berechtigungen speichern</button>
            <p id="flz-bq-permission-settings-status" role="status" aria-live="polite"></p>
        </form>
    <?php endif; ?>
</section>
