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
| A2 | Durchlauf und Curriculum-Snapshot | Die zentrale Vorlage mit mindestens zwei Modulen speichern, einen synthetischen Durchlauf anlegen, den zunächst unterminierten Snapshot terminieren und umordnen, danach die Vorlage ändern und alles neu laden. | Die Vorlagenversion steigt; der neue Durchlauf besitzt eine unabhängige, zunächst unterminierte Modulkopie. Terminierung, Reihenfolge und Vorlagenänderung verändern sich nicht gegenseitig; der Durchlauf bleibt vor vollständiger Terminierung unveröffentlichbar. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | Automatisierte Vertrags- und Persistenztests sind vorhanden; die erneute Runtime-/Browserabnahme steht aus. |
| A3 | Kalenderquellen und Vorschlag | Je einen Stand mit vollständigen Ferien-, Feiertags- und Sperrtagsquellen sowie mit fehlendem oder nicht verfügbarem Provider prüfen. | Nur der vollständige Stand darf als konfliktfrei erscheinen; unvollständige Quellen bleiben sichtbar und erzeugen keinen automatischen Vorschlag. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A4 | Interne und externe Dozentinnen | Eine interne PFK über die Suche der Organigrammrolle Pflegefachkräfte auswählen, eine UID außerhalb ihrer aktuellen Nextcloud-Gruppe direkt absenden, ein minimales externes Profil anlegen und beide Profile jeweils inaktiv sowie wieder aktiv setzen; anschließend mit einem Konto, das ausschließlich den internen PFK-Pool verwalten darf, direkte Requests auf Durchläufe, externe Profile und Anfragen prüfen. | Nur vorhandene Mitglieder der semantischen Rolle `pfk` werden gesucht und als interne UID gespeichert; fremde UIDs und unberechtigte Requests bleiben mutationsfrei abgewiesen. Die reine PFK-Pool-Berechtigung liefert keine Durchlaufdaten, externen Profile, E-Mail-Adressen oder Anfragen. Externe Profile enthalten nur die erlaubten Minimaldaten. Jede berechtigte Tabellenzeile erklärt und erlaubt versionsgesichert ausschließlich `Aktiv` und `Inaktiv`; inaktive Profile bleiben historisch sichtbar, sind für neue Zuordnungen aber gesperrt. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A5 | Veröffentlichung | Einen terminlich vollständigen Durchlauf veröffentlichen und unvollständige, abgesagte sowie manipulierte Varianten direkt anfragen. | Nur ein vollständiger, nicht abgesagter Durchlauf wird veröffentlicht; abgewiesene Requests verändern keinen Zustand. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A6 | Bedienbarkeit | Tabs, Tabellen, Curriculum, Dozentinnen und Dialoge nur per Tastatur bedienen; Fokus, 200-Prozent-Zoom, kleinen Viewport und beide Scrollrichtungen prüfen. | Alle Funktionen bleiben erreichbar, Fokus ist sichtbar, Tabellen schneiden keine Aktionen ab und es entsteht keine Tastaturfalle. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| A7 | Organisationsprovider | In der lokalen DDEV-Instanz den öffentlichen Organisations-V1-Snapshot und seine Rollenprojektion prüfen; zusätzlich fehlenden, inkompatiblen und fehlerhaften Providerzustand prüfen. | Nur ein gültiger Snapshot liefert Rollen. Fehlender oder ungültiger Provider bleibt ohne LocalBase-Fallback und erteilt keine Rechte. | [ ] erfolgreich [ ] nicht erfolgreich [x] nicht geprüft | DDEV-Auflösung ergab `valid=true` und 20 Rollen. Der ältere Adapterstand wurde nachträglich als Regression reproduziert; der aktuelle Stand besteht den Test. Deaktivierung, Entfernung und Reinstallation von `flz_organization` sind noch nicht geprüft (siehe ROADMAP). Der ursprüngliche zeitliche TDD-Ablauf ist nicht belegt. |

## Zeitlich begrenzter Admin-Vollzugriff

| ID | Was wird geprüft? | Auszuführende Schritte | Erwartetes Ergebnis | Ergebnis | Warum/Beleg/Abweichung |
|---|---|---|---|---|---|
| B1 | Datenschutzrolle ohne Adminrolle | Als nichtadministratives Mitglied von `Datenschutzbeauftragte` die isolierte Steuerung öffnen, Historie lesen und einem aktuellen nativen Testadmin für eine Stunde Zugriff erteilen. | Steuerung und Historie sind ohne BQ-Fachdaten erreichbar; die CSRF-geschützte Freigabe wird genau einmal app-lokal protokolliert. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B2 | Native Administration allein | Als nativer Admin ohne Datenschutzrolle Hinweis, Status-, Freigabe- und Widerrufspfad direkt aufrufen. | Es erscheint nur der sichere Hinweis ohne Direktlink; Steuerung und Mutation werden ohne Historienänderung verweigert. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B3 | Kombinierte Rollen | Dasselbe Konto zugleich als nativen Admin und Mitglied von `Datenschutzbeauftragte` ohne aktive Freigabe öffnen; Hinweis und Tabellenbereich per Tastatur bedienen. | Der Direktlink erscheint, Fokus bleibt sichtbar und der horizontale Tabellenbereich erreichbar; der Link selbst erteilt noch keinen Vollzugriff. | [x] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B4 | Manipulierte Ziele und Dauer | Als gewöhnliches Konto sowie mit leerem, fremdem oder nichtadministrativem Ziel und mit mehr als 24 Stunden direkte Requests senden. | Jeder Request wird ohne Freigabe- oder Auditmutation abgewiesen. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |
| B5 | CSRF und Wirksamkeitsende | Schreibrequest ohne gültigen Token senden; danach eine gültige Freigabe widerrufen, ablaufen lassen und dem Ziel den nativen Adminstatus entziehen. | CSRF-Fehler mutieren nichts; Widerruf, Ablauf und Rollenverlust beenden den fachlichen Vollzugriff sofort. | [ ] erfolgreich [ ] nicht erfolgreich [ ] nicht geprüft | |

## Ergebnis

- Ergebnis: Die app-spezifische Nacharbeit ist implementiert. Offene manuelle
  Nachweise bleiben über die nicht angekreuzten beziehungsweise als nicht
  geprüft markierten Fälle sichtbar.
- Abweichungen und reproduzierbare Schritte:
- Belege ohne Echtdaten:
