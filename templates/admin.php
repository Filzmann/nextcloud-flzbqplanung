<?php
script('adbqplanung','admin');
style('adbqplanung','style');
?>
<section id="adbq-admin" class="section bq-card" aria-labelledby="adbq-admin-heading">
    <h2 id="adbq-admin-heading">AD BQ-Planer</h2>
    <h3>Zeitlich begrenzter Admin-Vollzugriff</h3>
    <p>Native Nextcloud-Administration erteilt keinen automatischen Zugriff auf BQ-Planungsdaten. Eine Freigabe gilt je Administrationskonto für maximal 24 Stunden.</p>
    <form id="adbq-full-access-form">
        <label>Admin-Benutzerkennung <input name="targetUid" required maxlength="64" autocomplete="off"></label>
        <label>Dauer <select name="durationMinutes" required><option value="60">1 Stunde</option><option value="240">4 Stunden</option><option value="480">8 Stunden</option><option value="1440">24 Stunden</option></select></label>
        <label><input id="adbq-full-access-enabled" name="enabled" type="checkbox" required> Vollzugriff für diesen Zeitraum aktivieren</label>
        <button type="submit" class="primary">Freigabe aktivieren</button>
    </form>
    <p id="adbq-full-access-status" role="status" aria-live="polite"></p>
    <div class="bq-table-wrap"><table><caption>Protokollierte Admin-Vollzugriffszeiträume</caption><thead><tr><th>Ziel-Admin</th><th>Freigegeben von</th><th>Von</th><th>Geplant bis</th><th>Tatsächlich bis / Status</th><th>Aktion</th></tr></thead><tbody id="adbq-full-access-history"><tr><td colspan="6">Freigaben werden geladen.</td></tr></tbody></table></div>
</section>
