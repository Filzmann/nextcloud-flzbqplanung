# Roadmap

## Umgesetzt im ersten Kern

- App- und Repositorygrenze
- Konfigurierbare Arbeitstage und Startwochentag
- Erklärbare konfliktfreie Monatsvorschläge
- Praxisreflexionsvorschläge
- Veränderbare Curriculum-Snapshots
- Haupt-PFK und Modulabweichungen
- Admin-beschränkte, zugängliche Grundoberfläche
- Persistente BQ-Durchläufe und Curriculum-Module mit additiver Migration
- Optimistische Versionierung für Durchläufe und Module
- Konfigurierbare reguläre Kapazität bis maximal zehn Plätze
- Modulbezogene Nachholkapazität ohne Warteliste
- Entwurfs- und Veröffentlichungsstatus für terminierte Durchläufe
- Dozentinnenpool mit Nextcloud-validierten internen PFKs und minimalen
  externen Kontaktdaten
- Haupt-PFK je Durchlauf sowie externe Modul-Anfragen mit gekapselten
  Statusübergängen

## Nächste freigabepflichtige Pakete

1. Optionalen, fehlertoleranten Versandkanal für externe Anfragen ergänzen;
   der aktuelle Workflow erfasst ausschließlich den Kommunikationsstatus.
2. Granulares Rollenmodell für Planung, Lehre, Anwesenheit und Veröffentlichung.
3. Versionierter Kalenderprovider für Berliner Ferien und Feiertage mit
   kontrolliertem Missing-/Incompatible-Provider-Zustand.
4. Jahresansicht, Bearbeitungsdialoge und Konfliktanzeige ausbauen.
5. Optionaler Recruitment-Consumervertrag für terminlich vollständige BQs;
   kein direkter Zugriff und keine automatische Eignungsentscheidung.
6. PersonalDataProvider, Drittpersonensicht und fachlich freigegebene
   Retention-Regeln für interne und externe Dozentinnen.
