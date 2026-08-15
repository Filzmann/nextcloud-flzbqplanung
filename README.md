# AD BQ-Planer

Der AD BQ-Planer ist eine eigenständige Nextcloud-App für die terminliche und
inhaltliche Planung von Basisqualifizierungen.

Der erste Planungskern umfasst:

- konfigurierbare Kursdauer in Arbeitstagen und konfigurierbaren Starttag;
- erklärbare Monatsvorschläge unter Berücksichtigung von Ferien, Feiertagen,
  Brücken- und weiteren Sperrtagen;
- Praxisreflexionsvorschläge nach einem, drei und vier Monaten;
- versionierte Curriculum-Snapshots, deren Module umgeordnet und einzeln
  terminiert werden können; und
- eine Haupt-PFK mit optionalen abweichenden Dozentinnen je Modul.

Die App führt keine Bewerbungsakte und trifft keine Eignungs- oder
Einstellungsentscheidung. Eine spätere Anbindung an AD Recruitment verwendet
nur einen kleinen optionalen, versionierten Vertrag.

## Lokale Entwicklung

```bash
php tests/run.php
node tests/run-js.mjs
```

Lokale URL nach gesondert freizugebender Aktivierung:
`https://nextcloud-dev.ddev.site/apps/adbqplanung/`.
