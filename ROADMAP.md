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
- AD-Suite-Navigation über den kanonischen LocalBase-Produktkatalog mit
  explizitem Ausschluss aus Release-Bundles
- Versionierte LocalBase-Kalenderanbindung für Schulferien und gesetzliche
  Feiertage, konfigurierte Brückentage und sichere Status für aktuelle,
  veraltete, fehlende oder inkompatible Kalenderdaten
- Monatsvorschlag in Admin-API und Oberfläche ohne falsches Konfliktfrei-
  Versprechen bei fehlendem Provider
- Jahresvorschau mit getrenntem Kalenderstatus, Konfliktgründen und sicherer
  Teilfehlerbehandlung je Monat
- Versionierte Bearbeitung bestehender Entwurfsmodule und sichtbare Hinweise
  auf zeitliche Modulüberschneidungen
- Versionierte Bearbeitung der Stammdaten eines Entwurfs unter Erhalt aller
  bereits terminierten Module
- Transaktionale Änderung der Modulreihenfolge mit vollständiger
  Permutations- und Nebenläufigkeitsprüfung
- Zugängliche Tabgliederung in Durchläufe, Dozentinnen und Einstellungen;
  Bewerberinnenzuordnung bleibt ausschließlich in AD Recruitment

## Nächste freigabepflichtige Pakete

1. Optionalen, fehlertoleranten Versandkanal für externe Anfragen ergänzen;
   der aktuelle Workflow erfasst ausschließlich den Kommunikationsstatus.
2. Granulares Rollenmodell für Planung, Lehre, Anwesenheit und Veröffentlichung.
3. Optionaler Recruitment-Consumervertrag für terminlich vollständige BQs;
   kein direkter Zugriff und keine automatische Eignungsentscheidung.
4. PersonalDataProvider, Drittpersonensicht und fachlich freigegebene
   Retention-Regeln für interne und externe Dozentinnen.
5. Bundle-Freigabe erst nach grünen Release-, Datenschutz-, Rechte-,
   Integrations- und Staging-Nachweisen erteilen.
