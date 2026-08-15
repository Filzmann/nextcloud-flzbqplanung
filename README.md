# AD BQ-Planer

Der AD BQ-Planer ist eine eigenständige Nextcloud-App für die terminliche und
inhaltliche Planung von Basisqualifizierungen.

Er ist über den kanonischen LocalBase-Produktkatalog in die AD-Suite-Navigation
eingeordnet. Der aktuelle Entwicklungsstand wird noch nicht in Full-Suite-
oder Einzelprodukt-Bundles ausgeliefert.

Der erste Planungskern umfasst:

- konfigurierbare Kursdauer in Arbeitstagen und konfigurierbaren Starttag;
- erklärbare Monatsvorschläge unter Berücksichtigung von Ferien, Feiertagen,
  Brücken- und weiteren Sperrtagen;
- Praxisreflexionsvorschläge nach einem, drei und vier Monaten;
- versionierte Curriculum-Snapshots, deren Module umgeordnet und einzeln
  terminiert werden können; und
- eine Haupt-PFK mit optionalen abweichenden Dozentinnen je Modul.

Der persistente Admin-Arbeitsstand ergänzt BQ-Durchläufe und frei terminierbare
Curriculum-Module mit optimistischer Versionierung. Reguläre Durchläufe haben
aktuell höchstens zehn Plätze. Es gibt keine Warteliste; zusätzliche
Nachholplätze werden ausschließlich an einzelnen Modulen ausgewiesen.
Teilnehmerinnen und Umbuchungen zwischen früheren und späteren BQs bleiben in
AD Recruitment.

Der Dozentinnenbereich verwaltet interne PFKs als Nextcloud-UID und externe
Dozentinnen mit Name und E-Mail. Eine Haupt-PFK gilt für den gesamten
Durchlauf; bestätigte externe Anfragen überschreiben sie nur am jeweiligen
Modul. Der aktuelle Stand dokumentiert Anfrage, Zusage, Ablehnung und Absage,
versendet jedoch noch keine E-Mail.

Die App führt keine Bewerbungsakte und trifft keine Eignungs- oder
Einstellungsentscheidung. Eine spätere Anbindung an AD Recruitment verwendet
nur einen kleinen optionalen, versionierten Vertrag.

## Lokale Entwicklung

```bash
php tests/run.php
node tests/run-js.mjs
```

Lokale URL nach gesondert freizugebender Aktivierung:
`https://nextcloud-dev.ddev.site/index.php/apps/adbqplanung/`.
