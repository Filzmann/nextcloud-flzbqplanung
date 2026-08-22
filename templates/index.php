<?php

declare(strict_types=1);

use OCA\AdBqPlanning\AppInfo\Application;

script(Application::APP_ID, 'main');
style(Application::APP_ID, 'style');

$runs = is_array($_['runs'] ?? null) ? $_['runs'] : [];
$settings = is_array($_['settings'] ?? null) ? $_['settings'] : [];
$lecturers = is_array($_['lecturers'] ?? null) ? $_['lecturers'] : [];
$teachingRequests = is_array($_['teachingRequests'] ?? null) ? $_['teachingRequests'] : [];
$internalLecturers = array_values(array_filter($lecturers, static fn (array $lecturer): bool => ($lecturer['kind'] ?? '') === 'internal' && ($lecturer['active'] ?? false)));
$externalLecturers = array_values(array_filter($lecturers, static fn (array $lecturer): bool => ($lecturer['kind'] ?? '') === 'external' && ($lecturer['active'] ?? false)));
$lecturersById = [];
foreach ($lecturers as $lecturer) $lecturersById[(int)$lecturer['id']] = $lecturer;
$moduleLabels = [];
foreach ($runs as $run) {
    foreach (($run['modules'] ?? []) as $module) $moduleLabels[(int)$module['id']] = (string)$run['label'] . ' · ' . (string)$module['title'];
}
$lecturerLabel = static fn (array $lecturer): string => ($lecturer['kind'] ?? '') === 'internal'
    ? (string)($lecturer['nextcloudUid'] ?? '')
    : (string)($lecturer['displayName'] ?? '');
$weekdayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag'];
$statusNames = [
    'draft' => 'Entwurf',
    'published' => 'Veröffentlicht',
    'confirmed' => 'Bestätigt',
    'running' => 'Laufend',
    'completed' => 'Abgeschlossen',
    'cancelled' => 'Abgesagt',
];
$requestStatusNames = ['requested' => 'Angefragt', 'confirmed' => 'Zugesagt', 'declined' => 'Abgelehnt', 'cancelled' => 'Abgesagt'];
?>
<main id="adbqplanung-app" class="bq-app" aria-labelledby="bq-page-title">
    <div class="orgsuite-host" data-orgsuite data-suite="ad" data-current-app="adbqplanung"></div>
    <header class="bq-hero">
        <p class="bq-eyebrow">Basisqualifizierung</p>
        <h1 id="bq-page-title">BQ-Planer</h1>
        <p>Termine, Curriculum, Dozentinnen und Praxisreflexionen verlässlich vorbereiten.</p>
    </header>

    <p id="bq-feedback" class="bq-feedback" role="status" aria-live="polite"></p>

    <nav class="bq-tabs" role="tablist" aria-label="BQ-Planungsbereiche">
        <button type="button" id="bq-tab-runs" class="bq-tab" role="tab" aria-controls="bq-panel-runs" aria-selected="true" tabindex="0" data-tab-target="bq-panel-runs">Durchläufe</button>
        <button type="button" id="bq-tab-lecturers" class="bq-tab" role="tab" aria-controls="bq-panel-lecturers" aria-selected="false" tabindex="-1" data-tab-target="bq-panel-lecturers">Dozentinnen</button>
        <button type="button" id="bq-tab-settings" class="bq-tab" role="tab" aria-controls="bq-panel-settings" aria-selected="false" tabindex="-1" data-tab-target="bq-panel-settings">Einstellungen</button>
    </nav>

    <section id="bq-panel-settings" class="bq-tab-panel" role="tabpanel" aria-labelledby="bq-tab-settings" hidden>
        <section class="bq-grid" aria-label="BQ-Konfiguration">
        <form class="bq-card bq-form" data-endpoint="/api/settings" data-method="PUT">
            <h2>Planungsregeln</h2>
            <label>Arbeitstage
                <input name="workdayCount" type="number" min="1" max="30" required value="<?php p((string)($settings['workdayCount'] ?? 7)); ?>">
            </label>
            <label>Startwochentag
                <select name="startWeekday">
                    <?php foreach ($weekdayNames as $weekday => $name): ?>
                        <option value="<?php p((string)$weekday); ?>" <?php if ($weekday === (int)($settings['startWeekday'] ?? 5)): ?>selected<?php endif; ?>><?php p($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Standardkapazität
                <input name="defaultCapacity" type="number" min="1" max="10" required value="<?php p((string)($settings['defaultCapacity'] ?? 10)); ?>">
            </label>
            <label>Praxisreflexionen nach Monaten
                <input name="reflectionMonthOffsets" inputmode="numeric" required value="<?php p(implode(',', $settings['reflectionMonthOffsets'] ?? [1, 3, 4])); ?>">
            </label>
            <label>Brückentage
                <input name="bridgeDays" inputmode="numeric" value="<?php p(implode(',', $settings['bridgeDays'] ?? [])); ?>" placeholder="2026-05-15,2026-12-24">
                <small>Kommagetrennte Daten im Format JJJJ-MM-TT.</small>
            </label>
            <button type="submit" class="primary">Regeln speichern</button>
        </form>
        </section>
    </section>

    <section id="bq-panel-runs" class="bq-tab-panel" role="tabpanel" aria-labelledby="bq-tab-runs">
        <section class="bq-grid" aria-label="Neuen BQ-Durchlauf vorbereiten">
        <form class="bq-card bq-form" data-proposal-form>
            <h2>Monat vorschlagen</h2>
            <label>Planungsmonat
                <input name="proposalMonth" type="month" required>
            </label>
            <button type="submit">Terminvorschlag prüfen</button>
            <p id="bq-proposal-result" role="status" aria-live="polite"></p>
        </form>

        <form class="bq-card bq-form" data-endpoint="/api/runs" data-method="POST">
            <h2>Durchlauf anlegen</h2>
            <label>Bezeichnung
                <input name="label" maxlength="128" required placeholder="BQ 09/26">
            </label>
            <label>Beginn
                <input name="startsOn" type="date" required>
            </label>
            <label>Ende
                <input name="endsOn" type="date" required>
            </label>
            <label>Reguläre Plätze
                <input name="capacity" type="number" min="1" max="10" required value="<?php p((string)($settings['defaultCapacity'] ?? 10)); ?>">
            </label>
            <button type="submit" class="primary">Entwurf anlegen</button>
        </form>
        </section>

    <section class="bq-notice" aria-labelledby="bq-recruitment-title">
        <h2 id="bq-recruitment-title">Teilnehmerinnen aus Recruitment</h2>
        <p>Maximal zehn reguläre Plätze, keine Warteliste. Das Personalreferat ordnet Bewerberinnen in AD Recruitment zu und kann sie zwischen früheren und späteren BQs umbuchen.</p>
        <p>Nachholplätze werden ausschließlich für einzelne Curriculum-Module geplant und verändern die reguläre BQ-Kapazität nicht.</p>
    </section>

    <section class="bq-runs" aria-labelledby="bq-runs-title">
        <h2 id="bq-runs-title">BQ-Durchläufe</h2>
        <?php if ($runs === []): ?>
            <p class="bq-empty">Noch keine Durchläufe angelegt.</p>
        <?php endif; ?>
        <?php foreach ($runs as $run): ?>
            <article class="bq-run">
                <header>
                    <div>
                        <h3><?php p((string)$run['label']); ?></h3>
                        <p><?php p((string)$run['startsOn']); ?> bis <?php p((string)$run['endsOn']); ?> · <?php p((string)$run['capacity']); ?>/10 reguläre Plätze</p>
                        <p>Haupt-PFK: <?php
                            $lead = $lecturersById[(int)($run['leadLecturerId'] ?? 0)] ?? null;
                            p($lead === null ? 'noch nicht festgelegt' : $lecturerLabel($lead));
                        ?></p>
                    </div>
                    <span class="bq-badge"><?php p($statusNames[(string)$run['status']] ?? (string)$run['status']); ?></span>
                </header>

                <div class="bq-table-wrap">
                    <table>
                        <thead><tr><th>Modul</th><th>Termin</th><th>Dauer</th><th>Dozentin</th><th>Nachholplätze</th></tr></thead>
                        <tbody>
                        <?php foreach (($run['modules'] ?? []) as $module): ?>
                            <tr>
                                <td><?php p((string)$module['title']); ?></td>
                                <td><?php p((string)$module['date']); ?>, <?php p((string)$module['startsAt']); ?>–<?php p((string)$module['endsAt']); ?></td>
                                <td><?php p((string)$module['minutes']); ?> Minuten</td>
                                <td><?php
                                    $moduleLecturer = $lecturersById[(int)($module['lecturerId'] ?? 0)] ?? $lead;
                                    p($moduleLecturer === null ? 'Haupt-PFK offen' : $lecturerLabel($moduleLecturer));
                                ?></td>
                                <td><?php p((string)$module['additionalCapacity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (($run['status'] ?? '') === 'draft'): ?>
                    <?php if ($internalLecturers !== []): ?>
                        <form class="bq-inline-form" data-endpoint="/api/runs/<?php p((string)$run['id']); ?>/lead-lecturer" data-method="POST">
                            <input name="version" type="hidden" value="<?php p((string)$run['version']); ?>">
                            <label>Haupt-PFK
                                <select name="lecturerId" required>
                                    <?php foreach ($internalLecturers as $lecturer): ?>
                                        <option value="<?php p((string)$lecturer['id']); ?>"><?php p($lecturerLabel($lecturer)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <button type="submit">Haupt-PFK festlegen</button>
                        </form>
                    <?php endif; ?>
                    <form class="bq-module-form" data-endpoint="/api/runs/<?php p((string)$run['id']); ?>/modules" data-method="POST">
                        <input name="version" type="hidden" value="<?php p((string)$run['version']); ?>">
                        <label>Modulschlüssel <input name="moduleKey" pattern="[a-z0-9][a-z0-9-]{0,63}" required placeholder="pflege-1"></label>
                        <label>Titel <input name="title" maxlength="128" required placeholder="Pflege 1"></label>
                        <label>Datum <input name="date" type="date" required></label>
                        <label>Beginn <input name="startsAt" type="time" required></label>
                        <label>Ende <input name="endsAt" type="time" required></label>
                        <label>Minuten <input name="minutes" type="number" min="1" max="600" required></label>
                        <label>Nachholplätze <input name="additionalCapacity" type="number" min="0" max="10" value="0" required></label>
                        <button type="submit">Modul hinzufügen</button>
                    </form>
                    <?php if ($externalLecturers !== []): ?>
                        <?php foreach (($run['modules'] ?? []) as $module): ?>
                            <form class="bq-inline-form" data-endpoint="/api/modules/<?php p((string)$module['id']); ?>/teaching-requests" data-method="POST">
                                <input name="runVersion" type="hidden" value="<?php p((string)$run['version']); ?>">
                                <input name="moduleVersion" type="hidden" value="<?php p((string)$module['version']); ?>">
                                <label>Externe Dozentin für <?php p((string)$module['title']); ?>
                                    <select name="lecturerId" required>
                                        <?php foreach ($externalLecturers as $lecturer): ?>
                                            <option value="<?php p((string)$lecturer['id']); ?>"><?php p($lecturerLabel($lecturer)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <button type="submit">Externe Anfrage erfassen</button>
                            </form>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <form data-endpoint="/api/runs/<?php p((string)$run['id']); ?>/publish" data-method="POST">
                        <input name="version" type="hidden" value="<?php p((string)$run['version']); ?>">
                        <button type="submit" class="primary">Terminplanung veröffentlichen</button>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="bq-notice" aria-labelledby="bq-calendar-title">
        <h2 id="bq-calendar-title">Kalenderstatus</h2>
        <p>Automatische Vorschläge werden erst als konfliktfrei bezeichnet, wenn eine vollständige Ferien-, Feiertags- und Sperrtagsquelle vorliegt.</p>
    </section>
    </section>

    <section id="bq-panel-lecturers" class="bq-tab-panel" role="tabpanel" aria-labelledby="bq-tab-lecturers" hidden>
        <section class="bq-runs" aria-labelledby="bq-lecturers-title">
            <h2 id="bq-lecturers-title">Dozentinnenpool</h2>
            <div class="bq-grid">
                <form class="bq-card bq-form" data-endpoint="/api/lecturers" data-method="POST">
                    <h3>Interne PFK aufnehmen</h3>
                    <input name="kind" type="hidden" value="internal">
                    <input name="displayName" type="hidden" value="">
                    <input name="email" type="hidden" value="">
                    <label>Nextcloud-UID <input name="nextcloudUid" maxlength="64" required placeholder="ad-demo-pfk-a"></label>
                    <button type="submit">PFK aufnehmen</button>
                </form>
                <form class="bq-card bq-form" data-endpoint="/api/lecturers" data-method="POST">
                    <h3>Externe Dozentin aufnehmen</h3>
                    <input name="kind" type="hidden" value="external">
                    <input name="nextcloudUid" type="hidden" value="">
                    <label>Name <input name="displayName" maxlength="128" required></label>
                    <label>E-Mail <input name="email" type="email" maxlength="254" required></label>
                    <button type="submit">Dozentin aufnehmen</button>
                </form>
            </div>
            <?php if ($lecturers === []): ?><p class="bq-empty">Noch keine Dozentinnen im Pool.</p><?php endif; ?>
            <?php if ($lecturers !== []): ?>
                <div class="bq-table-wrap"><table>
                    <thead><tr><th>Typ</th><th>Referenz</th><th>Kontakt</th><th>Status</th></tr></thead>
                    <tbody><?php foreach ($lecturers as $lecturer): ?><tr>
                        <td><?php p(($lecturer['kind'] ?? '') === 'internal' ? 'Interne PFK' : 'Extern'); ?></td>
                        <td><?php p($lecturerLabel($lecturer)); ?></td>
                        <td><?php p((string)($lecturer['email'] ?? '–')); ?></td>
                        <td><?php p(($lecturer['active'] ?? false) ? 'Aktiv' : 'Inaktiv'); ?></td>
                    </tr><?php endforeach; ?></tbody>
                </table></div>
            <?php endif; ?>
        </section>

        <section class="bq-runs" aria-labelledby="bq-requests-title">
            <h2 id="bq-requests-title">Externe Anfragen</h2>
            <?php if ($teachingRequests === []): ?><p class="bq-empty">Noch keine externen Dozentinnen angefragt.</p><?php endif; ?>
            <?php foreach ($teachingRequests as $request): ?>
                <?php $requestLecturer = $lecturersById[(int)$request['lecturerId']] ?? null; ?>
                <article class="bq-request">
                    <p><strong><?php p($moduleLabels[(int)$request['moduleId']] ?? 'Unbekanntes Modul'); ?></strong> · <?php p($requestLecturer === null ? 'Unbekannte Dozentin' : $lecturerLabel($requestLecturer)); ?> · <?php p($requestStatusNames[(string)$request['status']] ?? (string)$request['status']); ?></p>
                    <?php if (($request['status'] ?? '') === 'requested'): ?>
                        <div class="bq-inline-actions">
                            <?php foreach (['confirmed' => 'Zusage erfassen', 'declined' => 'Ablehnung erfassen', 'cancelled' => 'Anfrage absagen'] as $target => $label): ?>
                                <form data-endpoint="/api/teaching-requests/<?php p((string)$request['id']); ?>/transition" data-method="POST">
                                    <input name="targetStatus" type="hidden" value="<?php p($target); ?>">
                                    <input name="version" type="hidden" value="<?php p((string)$request['version']); ?>">
                                    <button type="submit"><?php p($label); ?></button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (($request['status'] ?? '') === 'confirmed'): ?>
                        <form data-endpoint="/api/teaching-requests/<?php p((string)$request['id']); ?>/transition" data-method="POST">
                            <input name="targetStatus" type="hidden" value="cancelled">
                            <input name="version" type="hidden" value="<?php p((string)$request['version']); ?>">
                            <button type="submit">Zusage absagen</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>
    </section>
</main>
