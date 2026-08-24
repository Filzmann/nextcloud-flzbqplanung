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
- Die App verwendet den öffentlichen LocalBase-Produktkatalog und dessen
  Standalone-Navigationsdienst als mitgelieferte Suite-Infrastruktur. Sie
  kopiert keine Suite-Linkliste und leitet aus Navigation keine Rechte ab.
  Fehlende oder ungültige Katalogdaten dürfen keinen zusätzlichen Einstieg
  und keine Berechtigung erzeugen.
- Kalender-Sperrperioden werden über einen app-eigenen Port verarbeitet. Der
  LocalBase-Jahresvertrag Version 1 liefert Schulferien und gesetzliche
  Feiertage; konfigurierte Brückentage bleiben BQ-eigene Planungsregeln.
  `stale` ist nur mit sichtbarer Aktualitätseinschränkung nutzbar,
  `unavailable` oder inkompatible Daten erzeugen keinen automatischen
  Vorschlag.
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
- Die App ist im AD-Menü als Entwicklungsprodukt registriert, bleibt aber bis
  zur dokumentierten Release-Reife aus Full-Suite- und Einzelprodukt-Bundles
  ausgeschlossen.

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
- Der öffentliche Privacy-V1-Provider unterstützt zunächst ausschließlich
  interne PFKs über ihre geprüfte Nextcloud-UID. Externe Dozentinnenprofile
  bleiben bis zu einem sicher authentifizierten externen Subject-Vertrag aus
  der Selbstauskunft ausgeschlossen; Name oder E-Mail dienen nicht als
  Ersatzidentifikation.
- Schreibende Routen sind CSRF-geschützt. UI-Sichtbarkeit erteilt keine
  Rechte. SQL-Werte werden gebunden und Ausgaben escaped.
- Die Adminoberfläche gruppiert ihre Funktionen in die zugänglichen Tabs
  `Durchläufe`, `Dozentinnen` und `Einstellungen`; die Tabs unterstützen
  Maus, Pfeiltasten sowie Anfang/Ende und besitzen sichtbare Fokuszustände.

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

## Parent-Governance-Vertrag: 1

- Die für dieses Subrepository anwendbaren Regeln des Parent-Workspaces sind
  verbindlich. Dazu gehören insbesondere app-übergreifende ADRs und
  öffentliche Verträge, Repositorygrenzen sowie Workspace-, Delivery- und
  Release-Gates.
- Diese lokale `AGENTS.md` und die lokalen Skills bleiben die vollständige,
  ohne Parent-Checkout arbeitsfähige Repository-Steuerung. Die anwendbaren
  Parent-Regeln werden dafür hier oder in den lokalen Skills mitgeführt.
- Repository-lokale Regeln dürfen Parent-Verträge konkretisieren und verschärfen,
  aber nicht abschwächen oder umgehen.
- Bei einem Widerspruch gilt bis zur Klärung die strengere Regel. Die Arbeit
  stoppt, bis die kanonische Quelle bestimmt, die Regelprojektionen
  synchronisiert und eine erforderliche Entscheidung dokumentiert ist.
- Ist der Parent-Workspace nicht verfügbar, bleibt die lokale Steuerung
  wirksam. Vor Cross-App-, Release- oder Delivery-Arbeit muss ein vermuteter
  neuerer Parent-Stand oder eine Regelungslücke zuerst gegen den Parent
  geprüft werden.
