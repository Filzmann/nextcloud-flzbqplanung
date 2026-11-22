# Changelog

## Unreleased

- BQ-Berechtigungen und Organisationsgruppen getrennt: Planung, Lehre,
  Veröffentlichung und interner PFK-Pool werden im Nextcloud-Adminbereich
  einzeln den semantischen Organigrammrollen stellvertretende PDL, PDL und PFK
  zugeordnet. Alle vier Berechtigungen liegen standardmäßig bei der
  stellvertretenden PDL. Die Gruppenauflösung erfolgt fail-closed über den
  öffentlichen V1-Organisations-Snapshot von flz_organization ohne zweite
  LocalBase-Organisationsquelle;
  interne PFK-Eignung bleibt unabhängig an `pfk` gebunden und Pool-only-Zugriff
  legt weder Durchlaufdaten noch externe Profile oder Kontaktdaten offen.
- Der aktuelle Organisations-Snapshot wurde in DDEV als gültig mit 20 Rollen
  geprüft. Ein nachträglicher Regressionstest gegen die ältere, zuvor gestagte
  Quelle reproduzierte deren LocalBase-Fallback bei inkompatiblem Provider;
  die aktuelle Quelle besteht den Test ohne Fallback. Der ursprüngliche
  zeitliche TDD-Ablauf ist nicht belegt. Deaktivierungs-, Entfernungs- und
  Reinstall-Verhalten bleibt gemäß ROADMAP offen.
- Die Nacharbeit aus der manuellen Staging-Abnahme zeigt Ferien-, Feiertags-
  und BQ-Sperrtagsquellen einzeln an, begrenzt interne PFK-Auswahl auf die
  semantische Organigrammrolle Pflegefachkräfte, macht den versionsgesicherten
  Aktiv/Inaktiv-Status jeder Dozentin zugänglich bearbeitbar und bündelt die
  unverändert app-lokale Adminfreigabe in einem Tastatur-fähigen Klappbereich.
  Die zentrale Curriculum-Vorlage ist versionsgesichert bearbeitbar; neue
  Durchläufe erhalten daraus unabhängige, zunächst unterminierte Snapshots,
  während vorhandene Durchläufe unverändert bleiben.
- Nextcloud 35.0.1 durch Fresh Install, Upgrade 34→35 sowie Provider-,
  Berechtigungs-, Runtime-, UI-/API- und Asset-Smokes nachgewiesen und den
  unterstützten Bereich auf die lückenlosen Hauptversionen 33 bis 35
  erweitert. Nextcloud 36 bleibt ungeprüft.
- Die app-lokale Admin-Vollzugriffssteuerung aus dem technischen Adminbereich
  in die rollenabhängige BQ-Fachoberfläche verschoben. Nur
  `Datenschutzbeauftragte` können Historie lesen sowie Freigaben für aktuelle
  native Administrationskonten erteilen oder widerrufen; native Administration
  allein, gewöhnliche Konten und manipulierte Ziele bleiben mutationsfrei
  abgewiesen. Sichere Hinweis- und Direktlinkregeln sowie Datenschutz- und
  Berechtigungsprojektionen wurden daran angeglichen.
- App-eigenen Processing-Metadata-Katalog für vier BQ-Verarbeitungen über den
  öffentlichen Datenschutz-V1-Vertrag veröffentlicht.
- Dokumentations- und Steuerungsstruktur vereinheitlicht.
- Controller verwenden für HTTP-Statuscodes die auf Nextcloud 33 und 34
  vorhandene öffentliche Klasse `OCP\AppFramework\Http`.
- Die Einstiegsseite verwendet den öffentlichen `NoAdminRequired`-Import, so
  dass über semantische Organigrammrollen berechtigte Personen die Oberfläche erreichen, während die
  zentrale serverseitige Fachprüfung unverändert deny-by-default bleibt.
- Der PermissionProvider-Listener implementiert den öffentlichen
  Nextcloud-EventListener-Vertrag und wird dadurch von der
  Berechtigungsmatrix tatsächlich registriert.
- Der unterstützte Nextcloud-Bereich beginnt nach einem vollständigen
  Fresh-Install-Nachweis auf 33.0.7 und einem erfolgreichen Upgrade mit
  synthetischen BQ-Bestandsdaten auf 34.0.2 nun bei Nextcloud 33.

## 0.5.0-dev.2

- Aktueller Entwicklungsstand des BQ-Planers bei Einführung dieses
  Changelogs; der implementierte Umfang steht in `README.md`.
