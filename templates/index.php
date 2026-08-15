<?php

declare(strict_types=1);

use OCA\AdBqPlanning\AppInfo\Application;

script(Application::APP_ID, 'main');
style(Application::APP_ID, 'style');
?>
<main id="adbqplanung-app" class="bq-app" aria-labelledby="bq-page-title">
    <header class="bq-hero">
        <p class="bq-eyebrow">Basisqualifizierung</p>
        <h1 id="bq-page-title">BQ-Planer</h1>
        <p>Termine, Curriculum, Dozentinnen und Praxisreflexionen verlässlich vorbereiten.</p>
    </header>

    <section class="bq-grid" aria-label="Planungsbereiche">
        <article class="bq-card">
            <h2>Durchläufe</h2>
            <p>Standard: sieben Arbeitstage, Beginn am Freitag. Beide Werte bleiben konfigurierbar.</p>
            <p class="bq-state">Persistente Bearbeitung folgt im nächsten freigegebenen Paket.</p>
        </article>
        <article class="bq-card">
            <h2>Curriculum</h2>
            <p>Vorlagen werden je Durchlauf als veränderbarer, versionierter Snapshot verwendet.</p>
        </article>
        <article class="bq-card">
            <h2>Dozentinnen</h2>
            <p>Eine Haupt-PFK kann den Kurs begleiten; einzelne Module können abweichend besetzt werden.</p>
        </article>
        <article class="bq-card">
            <h2>Praxisreflexionen</h2>
            <p>Eintägige Folgemodule werden nach einem, drei und vier Monaten vorgeschlagen.</p>
        </article>
    </section>

    <section class="bq-notice" aria-labelledby="bq-calendar-title">
        <h2 id="bq-calendar-title">Kalenderstatus</h2>
        <p>Automatische Vorschläge werden erst als konfliktfrei bezeichnet, wenn eine vollständige Ferien-, Feiertags- und Sperrtagsquelle vorliegt.</p>
    </section>
</main>
