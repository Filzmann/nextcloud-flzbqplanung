<?php

declare(strict_types=1);

use OCA\AdBqPlanning\AppInfo\Application;

script(Application::APP_ID, 'main');
style(Application::APP_ID, 'style');

$runs = is_array($_['runs'] ?? null) ? $_['runs'] : [];
$settings = is_array($_['settings'] ?? null) ? $_['settings'] : [];
$weekdayNames = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag'];
$statusNames = [
    'draft' => 'Entwurf',
    'published' => 'Veröffentlicht',
    'confirmed' => 'Bestätigt',
    'running' => 'Laufend',
    'completed' => 'Abgeschlossen',
    'cancelled' => 'Abgesagt',
];
?>
<main id="adbqplanung-app" class="bq-app" aria-labelledby="bq-page-title">
    <header class="bq-hero">
        <p class="bq-eyebrow">Basisqualifizierung</p>
        <h1 id="bq-page-title">BQ-Planer</h1>
        <p>Termine, Curriculum, Dozentinnen und Praxisreflexionen verlässlich vorbereiten.</p>
    </header>

    <p id="bq-feedback" class="bq-feedback" role="status" aria-live="polite"></p>

    <section class="bq-grid" aria-label="BQ-Konfiguration und neuer Durchlauf">
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
            <button type="submit" class="primary">Regeln speichern</button>
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
                    </div>
                    <span class="bq-badge"><?php p($statusNames[(string)$run['status']] ?? (string)$run['status']); ?></span>
                </header>

                <div class="bq-table-wrap">
                    <table>
                        <thead><tr><th>Modul</th><th>Termin</th><th>Dauer</th><th>Nachholplätze</th></tr></thead>
                        <tbody>
                        <?php foreach (($run['modules'] ?? []) as $module): ?>
                            <tr>
                                <td><?php p((string)$module['title']); ?></td>
                                <td><?php p((string)$module['date']); ?>, <?php p((string)$module['startsAt']); ?>–<?php p((string)$module['endsAt']); ?></td>
                                <td><?php p((string)$module['minutes']); ?> Minuten</td>
                                <td><?php p((string)$module['additionalCapacity']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (($run['status'] ?? '') === 'draft'): ?>
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
</main>
