# Roadmap – AD BQ-Planer

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## Nächste freigabepflichtige Pakete

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
