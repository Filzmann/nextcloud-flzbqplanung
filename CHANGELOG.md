# Changelog

## Unreleased

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
