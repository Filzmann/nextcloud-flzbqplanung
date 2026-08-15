# Datenschutzaufgabe vor fachlicher Fertigstellung

## Daten und Personenreferenzen

- Interne PFKs und Bearbeitende: typisierte Nextcloud-UID.
- Externe Dozentinnen: app-eigene stabile ID, Name und nur die für Anfragen
  notwendigen Kontaktdaten.
- Bewerberinnen: im ersten App-Kern keine Daten. Eine spätere Teilnahme darf
  nur eine stabile Recruitment-Referenz und keinen Akteninhalt übernehmen.
- Freitext kann Drittpersonen enthalten und wird deshalb weder ungeprüft in
  Self-Service-Auskunft noch in technische Logs übernommen.

## Vor Fertigstellung zu entscheiden und zu testen

1. Zulässige Auskunft für Nextcloud-Nutzerinnen und externe Dozentinnen.
2. Drittpersonensicht in Curriculum-Hinweisen und Dozentinnenanfragen.
3. Aufbewahrungstrigger für Poolprofile, Anfragen, historische Zuordnungen und
   Bearbeitungsnachweise.
4. Konkrete Maßnahmen `DELETE`, `REMOVE_PERSON_REFERENCE`, `ANONYMIZE` oder
   `REVIEW` je Datenklasse.
5. Provider-, Rechte-, Negativ-, Sperr- und Grenztests gemäß der normativen
   Workspace-Datenschutzarchitektur.

Bis zur fachlichen Entscheidung gibt es keine automatische Löschung oder
erfundene Aufbewahrungsfrist.
