# Changelog

## Unreleased

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
  dass zugewiesene BQ-Rollengruppen die Oberfläche erreichen, während die
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
