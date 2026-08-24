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

Monatliche Vorschläge beziehen den versionierten LocalBase-Jahreskalender für
Schulferien und gesetzliche Feiertage ein. Brückentage werden als
kommagetrennte ISO-Daten konfiguriert. Ein veralteter Kalenderstand bleibt mit
sichtbarer Einschränkung nutzbar; bei fehlenden oder inkompatiblen Daten wird
kein automatischer Vorschlag ausgegeben.

Die Jahresvorschau prüft alle zwölf Monate in einem Lauf und weist je Monat
den automatischen Vorschlag, verworfene Starttermine samt Konfliktgründen oder
den Grund für einen fehlenden Vorschlag aus. Ein Monat ohne freien Termin
verdeckt die übrigen Monatsergebnisse nicht.

Der persistente Admin-Arbeitsstand ergänzt BQ-Durchläufe und frei terminierbare
Curriculum-Module mit optimistischer Versionierung. Reguläre Durchläufe haben
aktuell höchstens zehn Plätze. Es gibt keine Warteliste; zusätzliche
Nachholplätze werden ausschließlich an einzelnen Modulen ausgewiesen.
Teilnehmerinnen und Umbuchungen zwischen früheren und späteren BQs bleiben in
AD Recruitment.

Module eines Entwurfs können mit Schutz vor parallelen Änderungen bearbeitet
werden. Zeitlich überlappende Module eines Durchlaufs werden sichtbar
gekennzeichnet, ohne daraus eine zusätzliche fachliche Verbotsregel abzuleiten.
Auch Bezeichnung, Zeitraum und reguläre Kapazität eines Entwurfs bleiben
bearbeitbar, sofern der neue Zeitraum weiterhin alle terminierten Module
enthält und den konfigurierten Planungsregeln entspricht.
Die Reihenfolge der Module kann im Entwurf schrittweise geändert werden;
parallele Änderungen werden über die Durchlaufversion abgewehrt.

Der Dozentinnenbereich verwaltet interne PFKs als Nextcloud-UID und externe
Dozentinnen mit Name und E-Mail. Eine Haupt-PFK gilt für den gesamten
Durchlauf; bestätigte externe Anfragen überschreiben sie nur am jeweiligen
Modul. Der aktuelle Stand dokumentiert Anfrage, Zusage, Ablehnung und Absage,
versendet jedoch noch keine E-Mail.

Die Adminoberfläche gliedert die vorhandenen Funktionen wie die übrigen
Fachapps in die per Maus und Tastatur bedienbaren Tabs `Durchläufe`,
`Dozentinnen` und `Einstellungen`.

Das granulare Rollenmodell verwendet ausschließlich Nextcloud-Gruppen für
Planung, Lehre und Veröffentlichung. Nicht konfigurierte Rollen bleiben
deaktiviert; Nextcloud-Administrierende behalten vollständigen Zugriff und
konfigurieren die Gruppen im Tab `Einstellungen`. Die Oberfläche blendet
unzulässige Aktionen aus, während jeder API-Pfad die Fähigkeit zusätzlich
serverseitig prüft. Eine Anwesenheitsrolle wird erst mit den zugehörigen
Fachfunktionen aktiviert.

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
