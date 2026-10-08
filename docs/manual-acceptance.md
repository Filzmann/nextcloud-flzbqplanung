# Manuelle Abnahme – Filzmann BQ-Planer

Dieses Formular dokumentiert ausschließlich wiederholbare manuelle Prüfungen.
Es ist keine Produktivfreigabe und enthält keine echten Personen- oder
Kontaktdaten.

## Umgebung

- Datum: 4.10.26
- Nextcloud-Version: 33.2
- App-Version und Commit:
- Prüfer*in: simon

## Kernablauf

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| A1 | Start und Assets | BQ-Planer direkt sowie über den FLZ-Einstieg öffnen; Browserkonsole und geladene CSS-/JavaScript-Ressourcen prüfen. | App, Styles und Skripte laden ohne Fehler; ein fehlender Menüeintrag erteilt keine Fachrechte. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A2 | Durchlauf und Curriculum-Snapshot | Einen synthetischen Durchlauf aus der zentralen Curriculum-Vorlage anlegen, Module im Durchlauf umordnen und einen Termin ändern; Vorlage und Durchlauf danach neu laden. | Der Durchlauf besitzt einen eigenen stabilen Snapshot; dessen Änderungen verändern die zentrale Vorlage nicht rückwirkend. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A3 | Kalenderquellen und Vorschlag | Je einen Stand mit vollständigen Ferien-, Feiertags- und Sperrtagsquellen sowie mit fehlendem oder nicht verfügbarem Provider prüfen. | Nur der vollständige Stand darf als konfliktfrei erscheinen; unvollständige Quellen bleiben sichtbar und erzeugen keinen automatischen Vorschlag. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A4 | Interne und externe Dozentinnen | Eine interne PFK über die Nextcloud-Suche auswählen und ein minimales externes Profil anlegen; anschließend Anzeige, Bearbeitung und direkte Requests mit unberechtigtem Konto prüfen. | Interne Profile verwenden eine geprüfte Nextcloud-UID, externe nur die erlaubten Minimaldaten; fremde oder unberechtigte Zugriffe werden serverseitig verweigert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A5 | Veröffentlichung | Einen terminlich vollständigen Durchlauf veröffentlichen und unvollständige, abgesagte sowie manipulierte Varianten direkt anfragen. | Nur ein vollständiger, nicht abgesagter Durchlauf wird veröffentlicht; abgewiesene Requests verändern keinen Zustand. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A6 | Bedienbarkeit | Tabs, Tabellen, Curriculum, Dozentinnen und Dialoge nur per Tastatur bedienen; Fokus, 200-Prozent-Zoom, kleinen Viewport und beide Scrollrichtungen prüfen. | Alle Funktionen bleiben erreichbar, Fokus ist sichtbar, Tabellen schneiden keine Aktionen ab und es entsteht keine Tastaturfalle. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## Zeitlich begrenzter Admin-Vollzugriff

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| B1 | Datenschutzrolle ohne Adminrolle | Als nichtadministratives Mitglied von `Datenschutzbeauftragte` die isolierte Steuerung öffnen, Historie lesen und einem aktuellen nativen Testadmin für eine Stunde Zugriff erteilen. | Steuerung und Historie sind ohne BQ-Fachdaten erreichbar; die CSRF-geschützte Freigabe wird genau einmal app-lokal protokolliert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B2 | Native Administration allein | Als nativer Admin ohne Datenschutzrolle Hinweis, Status-, Freigabe- und Widerrufspfad direkt aufrufen. | Es erscheint nur der sichere Hinweis ohne Direktlink; Steuerung und Mutation werden ohne Historienänderung verweigert. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B3 | Kombinierte Rollen | Dasselbe Konto zugleich als nativen Admin und Mitglied von `Datenschutzbeauftragte` ohne aktive Freigabe öffnen; Hinweis und Tabellenbereich per Tastatur bedienen. | Der Direktlink erscheint, Fokus bleibt sichtbar und der horizontale Tabellenbereich erreichbar; der Link selbst erteilt noch keinen Vollzugriff. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B4 | Manipulierte Ziele und Dauer | Als gewöhnliches Konto sowie mit leerem, fremdem oder nichtadministrativem Ziel und mit mehr als 24 Stunden direkte Requests senden. | Jeder Request wird ohne Freigabe- oder Auditmutation abgewiesen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B5 | CSRF und Wirksamkeitsende | Schreibrequest ohne gültigen Token senden; danach eine gültige Freigabe widerrufen, ablaufen lassen und dem Ziel den nativen Adminstatus entziehen. | CSRF-Fehler mutieren nichts; Widerruf, Ablauf und Rollenverlust beenden den fachlichen Vollzugriff sofort. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## Ergebnis

- Ergebnis: Die app-spezifische Nacharbeit ist unter
  `FLZBQ-STAGING-FOLLOWUP` in `ROADMAP.md` erfasst. Die systemweite
  UI-Bündelung der temporären Adminfreigabe steht als `DP-11` im
  Parent-Zukunftsplan.
- Abweichungen und reproduzierbare Schritte:
- Belege ohne Echtdaten:
