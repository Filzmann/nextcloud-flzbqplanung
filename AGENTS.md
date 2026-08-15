# AGENTS.md – AD BQ-Planer

## Projekt

Nextcloud-App `adbqplanung` für die Planung von Basisqualifizierungen,
Curricula, Lehrenden und zeitlich folgenden Praxisreflexionen.
Diese Datei und die beiden lokal mitgeführten Skills bilden die vollständige Repository-Steuerung
beim direkten Öffnen dieses App-Repositories.

- Lokale URL: `https://nextcloud-dev.ddev.site/index.php/apps/adbqplanung/`
- App-ID: `adbqplanung`
- PHP-Namespace: `OCA\AdBqPlanning`

## Fach- und Repositorygrenze

- Diese App ist die kanonische Quelle für BQ-Durchlaufprogramme,
  Unterrichtstermine, Curriculum-Snapshots, Lehrendenzuordnungen und später
  Kapazität sowie Anwesenheit.
- AD Recruitment bleibt die kanonische Quelle für Bewerbungen,
  BQ-Zuordnungen, Eignungsentscheidungen und Einstellungsfreigaben. Es gibt
  keine direkten Zugriffe auf dessen Tabellen, Controller, Assets oder
  private Konfiguration.
- Der erste App-Kern besitzt **keine gemeinsame Laufzeitabhängigkeit**.
  Kalender-Sperrperioden werden über einen app-eigenen Port verarbeitet.
  Eine spätere LocalBase- oder Kalenderanbindung ist optional und beginnt nur
  mit einem freigegebenen, aktivierungs- und versionsbewussten öffentlichen
  Providervertrag. Bis dahin entsteht keine versteckte Runtime-Abhängigkeit.
- Fehlt ein Kalenderprovider, darf die App keine vermeintlich konfliktfreien
  automatischen Vorschläge behaupten. Manuelle Planung bleibt mit sichtbarem
  Vollständigkeitsstatus möglich.
- AD Recruitment erhält später ausschließlich stabile externe Durchlauf-IDs
  und notwendige terminliche Snapshots. Sein lokaler Fallback bleibt gültig.

## Fachregeln des ersten Kerns

- Standard sind sieben Arbeitstage und ein Start am Freitag; beide Werte sind
  innerhalb fachlich validierter Grenzen konfigurierbar.
- Reguläre Durchläufe besitzen höchstens zehn Plätze; der Standardwert zehn
  ist konfigurierbar. Die App führt keine Warteliste.
- Zusätzliche Nachholplätze gelten ausschließlich für einzelne
  Curriculum-Module und erhöhen nicht die reguläre Durchlaufkapazität.
- Teilnehmerinnen werden ausschließlich aus AD Recruitment zugeordnet. Das
  Personalreferat kann dort Zuordnungen zwischen früheren und späteren BQs
  ändern; die BQ-App kopiert weder Zuordnungen noch Bewerbungsakten.
- Arbeitstage sind Montag bis Freitag. Automatische Vorschläge überspringen
  Ferien, Feiertage sowie konfigurierte Brücken- und Sperrtage vollständig.
  Manuelle Abweichungen werden nicht still vorgenommen.
- Praxisreflexionen sind eintägige Zusatzmodule nach einem, drei und vier
  Kalendermonaten ab dem letzten Haupt-BQ-Tag. Ein blockierter Zieltag wird
  nachvollziehbar auf den nächsten freien Arbeitstag verschoben.
- Curriculum-Vorlagen werden versioniert. Jeder Durchlauf verwendet einen
  eigenen Snapshot, der zeitlich verändert oder umgeordnet werden kann, ohne
  die Vorlage rückwirkend zu verändern.
- Eine interne PFK kann als durchgängige Hauptdozentin gesetzt werden.
  Einzelne Module dürfen auf andere interne oder externe Dozentinnen aus dem
  Pool abweichen.
- Interne PFK-UIDs werden vor der Speicherung gegen Nextcloud geprüft.
  Externe Profile speichern ausschließlich Name und E-Mail als minimale
  Kontaktdaten. Der aktuelle Anfrageworkflow versendet keine Nachrichten,
  sondern dokumentiert den extern erfolgten Kommunikationsstatus.
- Nur veröffentlichte, terminlich vollständige und nicht abgesagte
  Durchläufe dürfen später als verfügbare BQs an Recruitment gemeldet werden.

## Architektur, Rechte und Datenschutz

- Controller bleiben dünn; Fachlogik, Persistenz, Darstellung und externe
  Provider bleiben getrennt.
- Rechte gelten deny by default und werden serverseitig geprüft. Solange das
  granulare Rollenmodell nicht implementiert ist, bleibt die App-Oberfläche
  auf Nextcloud-Administrierende beschränkt.
- Dozentinnenprofile enthalten personenbezogene Daten. Interne PFKs werden
  mit Nextcloud-UID referenziert, externe Dozentinnen mit app-eigener stabiler
  ID und minimalen Kontaktdaten. Bewerberakten werden nicht kopiert.
- Vor fachlicher Fertigstellung müssen PersonalDataProvider,
  Drittpersonensicht, Retention-Trigger und Maßnahmen für Dozentinnen- und
  Bearbeitungsreferenzen konkret umgesetzt und getestet sein.
- Schreibende Routen sind CSRF-geschützt. UI-Sichtbarkeit erteilt keine
  Rechte. SQL-Werte werden gebunden und Ausgaben escaped.

## Arbeitsweise und Tests

- Die lokalen Skills `work-in-nextcloud-app` und `test-driven-change` sind
  verbindlich.
- Neue Funktionen und Verhaltensänderungen folgen Red–Green–Refactor.
- Schnelle Prüfungen: `php tests/run.php` und `node tests/run-js.mjs`.
- Migrationen benötigen Fresh-Install-, Upgrade-, Wiederholungs- und
  Integritätsnachweise in DDEV.
- DDEV-Mount: `/var/www/html/html/custom_apps/adbqplanung`.
- Keine Commits, Pushes, Releases, Deployments oder Nextcloud-Aktivierung ohne
  ausdrückliche Freigabe.
