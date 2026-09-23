# Manuelle Abnahme – AD BQ-Planer

Dieses Formular dokumentiert ausschließlich wiederholbare manuelle Prüfungen.
Es ist keine Produktivfreigabe und enthält keine echten Personen- oder
Kontaktdaten.

## Umgebung

- Datum:
- Nextcloud-Version:
- App-Version und Commit:
- Prüfer*in:

## Kernablauf

- [ ] App startet und Assets werden korrekt geladen.
- [ ] Durchlauf, Curriculum-Snapshot und Termine sind verständlich bedienbar.
- [ ] Ferien, Feiertage und nicht verfügbare Provider werden sichtbar korrekt behandelt.
- [ ] Interne und externe Lehrendenprofile bleiben in ihren Daten- und Rechtegrenzen.
- [ ] Veröffentlichung und unberechtigte Direktaufrufe wurden positiv beziehungsweise negativ geprüft.
- [ ] Tastatur, Fokus, Tabs, kleine Viewports und Scrollverhalten wurden geprüft.

## Zeitlich begrenzter Admin-Vollzugriff

- [ ] Ein nichtadministratives Mitglied von `Datenschutzbeauftragte` kann die
  isolierte Freigabesteuerung öffnen, Historie lesen und einem aktuellen
  nativen Admin CSRF-geschützt Zugriff erteilen, ohne BQ-Fachdaten zu laden.
- [ ] Ein nativer Admin ohne Datenschutzrolle sieht ausschließlich den sicheren
  Hinweis ohne Direktlink; direkte Status-, Freigabe- und Widerrufrequests
  werden ohne Historienänderung verweigert.
- [ ] Ein Konto mit kombinierter Admin- und Datenschutzrolle sieht bei fehlender
  Freigabe den Direktlink; Fokus und horizontaler Tabellenbereich bleiben per
  Tastatur bedienbar.
- [ ] Gewöhnliche Konten sowie leere, fremde oder nichtadministrative Ziele und
  Dauern über 24 Stunden werden ohne Auditmutation abgewiesen.
- [ ] Requests ohne gültigen CSRF-Token mutieren nichts; Widerruf, Ablauf und
  Verlust des nativen Adminstatus beenden den fachlichen Vollzugriff.

## Ergebnis

- Ergebnis:
- Abweichungen und reproduzierbare Schritte:
- Belege ohne Echtdaten:

## Automatisierter lokaler Nachweis vom 11.09.2026

Im Rahmen der risikoarmen Luna-Prüfung wurden ausschließlich lokale, nicht
mutierende Prüfungen ausgeführt:

| Prüfung | Ergebnis | Aussagegrenze |
|---|---|---|
| `php tests/run.php` | erfolgreich | 62 PHP-Vertrags- und Fachtests sind grün, einschließlich Rollen/Rechte, Adminfreigabe, Kalenderprovider, Planung, Curriculum-Snapshots, Publikation, Lehrenden und negativer Fälle. |
| `node tests/run-js.mjs` | erfolgreich | JavaScript- und UI-Verträge sind grün. |
| `git diff --check` | erfolgreich | Keine Whitespace-Fehler im aktuellen Arbeitsbaum. |

DDEV, `occ`, Installation, App-Aktivierung und BQ-/Lehrendendaten wurden
nicht verändert. Der Nachweis ersetzt weder die manuelle Oberflächeprüfung
noch die dokumentierte Release-Reifeentscheidung für diese Entwicklungsapp.
