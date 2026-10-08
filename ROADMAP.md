# Roadmap – Filzmann BQ-Planer

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## Nächste freigabepflichtige Pakete

### FLZBQ-STAGING-FOLLOWUP – Nacharbeit aus der manuellen Abnahme

- Die Vollständigkeit von Ferien-, Feiertags- und BQ-eigenen Sperrtagsquellen
  in der Vorschlagsoberfläche so deutlich darstellen wie im Urlaubsplaner;
  ohne vollständige Quelle darf kein Vorschlag als konfliktfrei erscheinen.
- Die zentrale versionierte Curriculum-Vorlage verständlich bearbeiten und
  daraus je Durchlauf einen weiterhin editier- und verschiebbaren Snapshot
  verwenden. Die bereits geltende Trennung von Vorlage und Snapshot bleibt
  dabei die einzige Datenwahrheit.
- Das Feld für interne PFKs als Suche über die in Nextcloud vorhandenen
  Mitglieder der konfigurierten PFK-Gruppe ausführen und Auswahl sowie
  serverseitige UID-Prüfung abnehmen.
- Bedeutung und Übergänge des Dozentinnenstatus erklären und die zulässigen
  Werte in allen Zeilen der Dozentinnentabelle zugänglich bearbeitbar machen.
- Die systemweite UI-Bündelung der temporären Adminfreigabe folgt `DP-11` im
  Parent-Zukunftsplan; Rechte, Audit und Persistenz bleiben app-lokal.
- Das manuelle Abnahmeformular enthält für jeden Fall konkrete Schritte,
  erwartete Ergebnisse und ein eigenes Evidenzfeld.

1. Optionalen, fehlertoleranten Versandkanal für externe Anfragen erst nach
   ausdrücklicher anbieterbezogener Datenschutzfreigabe ergänzen; der aktuelle
   Workflow erfasst ausschließlich den Kommunikationsstatus.
2. Anwesenheitsrolle erst zusammen mit den fachlichen Anwesenheitsfunktionen
   aktivieren.
3. Optionaler Recruitment-Consumervertrag für terminlich vollständige BQs;
   kein direkter Zugriff und keine automatische Eignungsentscheidung.
4. Datenschutzumfang vervollständigen:
   - Interne PFKs und Bearbeitende bleiben typisierte Nextcloud-UIDs.
   - Externe Dozentinnen verwenden eine app-eigene stabile ID, Name und nur
     die für Anfragen notwendigen Kontaktdaten.
   - Bewerberinnen dürfen später nur über eine stabile
     Recruitment-Referenz, niemals über Akteninhalte übernommen werden.
   - Externe Profile und Anfragen nach der beschlossenen Zwölfmonatsregel
     app-lokal löschen und Personenreferenzen aus erforderlichen historischen
     Curriculumstrukturen entfernen; interne PFK- und Bearbeitungsbezüge
     benötigen noch eine eigene Aufbewahrungsentscheidung.
   - Provider-, Rechte-, Negativ-, Sperr-, Restore- und Grenztests ergänzen.
     Bis zu diesem Ausführungsnachweis gibt es keine automatische Löschung.
5. Bundle-Freigabe erst nach grünen Release-, Datenschutz-, Rechte-,
   Integrations- und Staging-Nachweisen erteilen.
