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
    <h2 id="flz-bq-admin-heading"><?php p($l->t('Filzmann BQ-Planer')); ?></h2>
    <p><?php p($l->t('Hier ordnest du die vier fachlichen BQ-Berechtigungen den passenden Organigrammrollen zu. Die Berechtigungsmatrix zeigt Berechtigungen als Zeilen und Organigrammrollen als Spalten. Gruppen werden nicht als Berechtigungen gespeichert; ihre aktuellen Nextcloud-IDs werden aus dem öffentlichen V1-Vertrag von flz_organization ermittelt.')); ?></p>
    <p><strong><?php p($l->t('Standard:')); ?></strong><?php p($l->t(' Die Stellvertretende Pflegedienstleitung erhält alle vier Berechtigungen. Pflegedienstleitung und Pflegefachkräfte können je Berechtigung zusätzlich ausgewählt werden. Keine der Rollen erhält automatisch eine andere Berechtigung.')); ?></p>

    <?php if (!$canConfigure): ?>
        <p role="alert"><?php p($l->t('Für Änderungen ist zusätzlich zur Nextcloud-Administration ein aktiver, zeitlich begrenzter BQ-Admin-Vollzugriff erforderlich.')); ?></p>
    <?php else: ?>
        <?php if (($selection['message'] ?? '') !== ''): ?>
            <p role="alert"><?php p((string)$selection['message']); ?></p>
        <?php endif; ?>
        <form id="flz-bq-permission-settings" class="bq-form">
            <div class="bq-table-wrap">
                <table class="bq-matrix">
                    <caption><?php p($l->t('Berechtigungsmatrix')); ?></caption>
                    <thead><tr>
                        <th scope="col"><?php p($l->t('Berechtigung')); ?></th>
                        <?php foreach ($options as $option): ?>
                            <?php $roleKey = (string)($option['roleKey'] ?? ''); ?>
                            <th scope="col"><?php p((string)($option['label'] ?? $roleKey)); ?> <small><?php p((string)($option['groupId'] ?? '')); ?><?php if (!($option['exists'] ?? false)): ?> – <?php p($l->t('Gruppe fehlt')); ?><?php endif; ?></small></th>
                        <?php endforeach; ?>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($permissionSections as $fieldName => [$settingsKey, $label, $description]): ?>
                        <?php $selectedRoles = is_array($settings[$settingsKey] ?? null) ? $settings[$settingsKey] : []; ?>
                        <tr data-capability="<?php p($settingsKey); ?>">
                            <th scope="row"><?php p($label); ?> <small><?php p($description); ?></small></th>
                            <?php foreach ($options as $option): ?>
                                <?php
                                $roleKey = (string)($option['roleKey'] ?? '');
                                $exists = (bool)($option['exists'] ?? false);
                                $optionLabel = (string)($option['label'] ?? $roleKey);
                                ?>
                                <td><label>
                                    <input
                                        type="checkbox"
                                        name="<?php p($fieldName); ?>"
                                        value="<?php p($roleKey); ?>"
                                        <?php if (in_array($roleKey, $selectedRoles, true)): ?>checked<?php endif; ?>
                                        <?php if (!$exists): ?>disabled<?php endif; ?>
                                        aria-label="<?php p($l->t('%1$s: %2$s', [$label, $optionLabel])); ?>"
                                    >
                                    <?php p($optionLabel); ?>
                                </label></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="submit" class="primary" <?php if (!($selection['available'] ?? false)): ?>disabled<?php endif; ?>><?php p($l->t('Berechtigungen speichern')); ?></button>
            <p id="flz-bq-permission-settings-status" role="status" aria-live="polite"></p>
        </form>
    <?php endif; ?>
</section>
