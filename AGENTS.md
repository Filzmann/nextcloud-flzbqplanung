# AGENTS.md – AD BQ-Planer

## Projekt

Nextcloud-App `adbqplanung` für die Planung von Basisqualifizierungen,
Curricula, Lehrenden und zeitlich folgenden Praxisreflexionen.
Diese Datei und die beiden lokal mitgeführten Skills bilden die vollständige Repository-Steuerung
beim direkten Öffnen dieses App-Repositories.

- Lokale URL: `https://nextcloud-dev.ddev.site/index.php/apps/adbqplanung/`
- App-ID: `adbqplanung`
- PHP-Namespace: `OCA\AdBqPlanning`
- Offene app-lokale Produktplanung: `ROADMAP.md`

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
- Rechte gelten deny by default und werden serverseitig geprüft. Planung,
  Lehre und Veröffentlichung werden getrennten, adminseitig konfigurierten
  Nextcloud-Gruppen zugeordnet. Ohne konfigurierte Gruppe bleibt die jeweilige
  Fähigkeit deaktiviert. Native Nextcloud-Administrierende erhalten nur nach
  einer app-lokalen, UID-genauen Freigabe für höchstens 24 Stunden
  vollständigen Fachzugriff; Beginn, geplantes Ende und Widerruf werden
  protokolliert. Ausschließlich Mitglieder der nativen Gruppe
  `Datenschutzbeauftragte` lesen die Historie und erteilen oder widerrufen
  Freigaben in der BQ-Fachoberfläche. Ein fehlender Vollzugriff wird nur dem
  betroffenen Administrationskonto angezeigt; ein direkter Link zur
  Freigabesteuerung erscheint ausschließlich, wenn dasselbe Konto zugleich
  Mitglied von `Datenschutzbeauftragte` ist. Die Anwesenheitsrolle bleibt bis zu konkreten
  Anwesenheitsfunktionen reserviert und erteilt aktuell keine Fähigkeit.
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
- PersonalDataProvider und PermissionProvider umfassen auch die app-lokale
  Adminfreigabe. Die Selbstauskunft nennt nur die eigene Rolle im
  Freigabevorgang und keine Kennungen anderer beteiligter Administrator*innen.
- Der app-eigene Processing-Katalog unter
  `resources/privacy-processing.json` ist die kanonische Metadatenquelle für
  Durchlauf/Curriculum/Terminplanung, Dozentinnenprofile und Zuordnungen,
  externe Anfragezustände sowie temporären Admin-Vollzugriff. Er enthält keine
  personenbezogenen Laufzeitdaten und markiert ungeklärte Rechtsgrundlagen,
  Empfänger-, Retention-, Backup-, Kommunikations- und Betroffenenrechtsfragen
  mit `PRIVACY-DECISION-REQUIRED`. Der öffentliche V1-Provider liest
  ausschließlich diesen Katalog; eine zweite Registry ist verboten.
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
- Migrationen benötigen Fresh-Install-, Wiederholungs- und Integritätsnachweise
  in DDEV; historische App-Upgrades nur bei einem Erhaltungsgrund nach der
  unten projizierten Entwicklungsphasenregel.
- DDEV-Mount: `/var/www/html/html/custom_apps/adbqplanung`.
- Keine Commits, Pushes, Releases, Deployments oder Nextcloud-Aktivierung ohne
  ausdrückliche Freigabe.

## Dokumentenverantwortung

- `README.md` beschreibt ausschließlich den aktuellen nutzbaren Stand,
  Installation, Betrieb, Tests und den Dokumentationsindex.
- `ROADMAP.md` enthält ausschließlich offene, zurückgestellte oder
  freigabepflichtige Arbeit und Entscheidungen.
- `CHANGELOG.md` dokumentiert erledigte Änderungen releasebezogen; erledigte
  Checklisten verbleiben nicht in der Roadmap.
- `docs/architecture.md` ist die ausführliche Quelle für geltende fachliche
  und technische Architekturverträge.
- `docs/manual-acceptance.md` enthält wiederholbare manuelle Prüfungen und
  keine Produktplanung.
- `AGENTS.md` enthält ausschließlich verbindliche Arbeits-, Sicherheits-,
  Architektur- und Prüfregeln. Zusätzliche Dokumente werden in `README.md`
  mit eindeutiger Zuständigkeit eingeordnet.

## Parent-Governance-Vertrag: 2

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

### Entwicklungsphase und Kompatibilitätsbedarf

Entscheidung vom 5. September 2026: Das Gesamtprojekt befindet sich vollständig
in der Entwicklung. Es gibt kein PROD, keinen produktiven Datenbestand und
keinen bereits betriebenen Bestand mit zu erhaltendem Upgradepfad. STAGING
ist eine wegwerfbare Entwicklungs- und Integrationsumgebung und darf im
konkret beauftragten Reinstall vollständig neu aufgebaut werden. Wenige
externe Testnutzer ändern diese Einordnung nicht.

Vor einer Datenmigration, Legacy-Unterstützung, Compatibility Layer,
Deprecated API, Dual-Read/Dual-Write, einem Altschema-Fallback, Übergangsformat
oder der Unterstützung historischer Entwicklungsstände wird geprüft:

1. Wurde der betroffene Zustand jemals produktiv eingesetzt?
2. Benötigen reale Daten oder Nutzer seine Erhaltung?
3. Gibt es einen anderen konkreten technischen Erhaltungsgrund, insbesondere
   einen geltenden Plattform- oder externen API-Vertrag?

Sind alle relevanten Antworten nein, ist die saubere Breaking-Change-/
Reinstall-Lösung der Standard. Frühere rein interne Entwicklungsstände
begründen weder Abwärtskompatibilität noch eine Deprecationfrist.
Entwicklungsschemata dürfen durch ein kanonisches Installationsschema ersetzt,
alte interne APIs und Konfigurationsformate samt ausschließlich dafür
benötigten Adaptern und Tests entfernt werden. Architekturqualität und der
saubere Zielzustand haben Vorrang. Nextclouds nötige Installationsmigrationen
bleiben erhalten; ein Verzeichnisname `Migration` beweist keine Altlast.

Breaking Changes werden im selben Änderungskontext vollständig durchgezogen:
betroffene Provider, Consumer, standardisierte APIs, Vertragsversionen,
Metadaten, Tests und Dokumentation müssen zusammenpassen. Unterstützte
Nextcloud-/openDesk-Plattformverträge, externe Standards, Autorisierung und
Datenschutz gelten unverändert. Fehlende oder inkompatible optionale Provider
bleiben kontrolliert sichtbar. Ein Reinstall erlaubt keine privaten
Fremdtabellenzugriffe oder parallel erfundenen Plattformmechanismen.

Vor destruktiver Arbeit werden die tatsächlich benötigten externen
Testidentitäten, Gruppen, Rollen und nicht reproduzierbaren Testdaten gezielt
gesichert oder über bestehende native Setup-Strukturen reproduzierbar gemacht.
Echte Personen- und Zugangsdaten bleiben außerhalb von Git. Diese begrenzte
Sicherung begründet keine allgemeine Legacy-Unterstützung. Ein Reinstall
bleibt ein normaler unterstützter Entwicklungsweg; der vorhandene
Compatibility-Workflow besitzt den Fresh-Install-Nachweis, dessen aktueller
Belegstatus in `docs/workspace.md` beschrieben ist.

Diese Phase endet ausschließlich durch einen ausdrücklich dokumentierten,
von Simon freigegebenen **Production-Readiness-/Production-Freeze-Entscheid**.
Ein Release Candidate, eine Versionsnummer, ein Staging-Deployment oder ein
externer Testzugang lösen den Wechsel nicht aus. Der Entscheid wird in dieser
kanonischen Lifecycle-Quelle mit Datum, Geltungsbereich und betroffenem
Versions-/Datenstand festgehalten und in die lokale Steuerung projiziert.
Dann werden Upgradepfade, Datenbankmigrationen, Persistenz, Backup/Restore,
Rollback, Release-/API-Kompatibilitätszusagen, Deployment-/Freigabeprozess und
PROD→STAGING/COPY-Strategie neu bewertet. Eine vollständige PROD-Governance
wird jetzt nicht vorweggenommen.

Diese Regel entscheidet den Kompatibilitätsbedarf, erweitert aber keinen
Repository-Schreibauftrag und ersetzt keine Freigabe für eine konkrete
destruktive Aktion. Lokale Regelprojektionen folgen dem bestehenden
`docs/parent-governance-contract.md`; ein unsynchronisierter Einzel-Checkout
darf keinen abweichenden Phasenstand stillschweigend annehmen.

### Prüfaufwand

- Vor einem neuen Test, Scan, Linter, Architektur- oder Systemcheck wird
  geprüft, welcher bestehende Check dieselbe Eigenschaft bereits nachweist.
  Diesen erweitern oder sein nachweislich passendes Ergebnis wiederverwenden;
  ein zusätzlicher Check braucht eine benannte zusätzliche Fehlerklasse oder
  Vertrauensgrenze. Gleicher Input, gleiche Prüfung, gleiche Fehlerklasse und
  gleiche Phase begründen keinen zweiten Lauf. Gestaffelte Unit-, Contract-
  und Runtime-Nachweise bleiben erhalten. Die dokumentierten lokalen
  Prüfeinstiege bestimmen Umfang und Ergebnisgültigkeit.
