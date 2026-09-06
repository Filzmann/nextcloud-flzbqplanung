# Roadmap – AD BQ-Planer

Diese Datei enthält ausschließlich offene Arbeit, zurückgestellte Vorhaben
und Freigabegates. Der aktuelle Funktionsumfang steht in `README.md`,
erledigte Änderungen in `CHANGELOG.md` und geltende Architektur in
`docs/architecture.md`.

## Nächste freigabepflichtige Pakete

1. Optionalen, fehlertoleranten Versandkanal für externe Anfragen ergänzen;
   der aktuelle Workflow erfasst ausschließlich den Kommunikationsstatus.
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
   - Auskunft, Drittpersonensicht in Curriculum-Hinweisen und Anfragen sowie
     Aufbewahrungstrigger für Poolprofile, Anfragen, historische Zuordnungen
     und Bearbeitungsnachweise fachlich entscheiden.
   - Je Datenklasse `DELETE`, `REMOVE_PERSON_REFERENCE`, `ANONYMIZE` oder
     `REVIEW` festlegen und Provider-, Rechte-, Negativ-, Sperr- und
     Grenztests ergänzen. Bis dahin gibt es keine automatische Löschung und
     keine erfundene Frist.
5. Bundle-Freigabe erst nach grünen Release-, Datenschutz-, Rechte-,
   Integrations- und Staging-Nachweisen erteilen.
