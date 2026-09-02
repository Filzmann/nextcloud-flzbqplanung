# Architektur – AD BQ-Planer

## Verantwortung

Der BQ-Planer ist die kanonische Quelle für BQ-Durchläufe,
Curriculum-Snapshots, Unterrichtstermine, Lehrendenprofile und
Praxisreflexionen. AD Recruitment bleibt Eigentümerin von Bewerbungen,
Eignungsentscheidungen und Teilnehmerzuordnungen.

## Verträge

- Curriculum-Vorlagen werden versioniert; jeder Durchlauf verwendet einen
  eigenen Snapshot.
- Automatische Vorschläge beachten Arbeitstage sowie den read-only
  LocalBase-Vertrag für Ferien und Feiertage. Ein fehlender Provider wird
  sichtbar und niemals als konfliktfreie Leerliste interpretiert.
- Externe Lehrendenprofile enthalten nur stabile App-ID, Name und notwendige
  Kontaktadresse; interne PFKs werden über geprüfte Nextcloud-UIDs
  referenziert.
- Eine spätere Recruitment-Anbindung verwendet ausschließlich einen kleinen,
  optionalen Capability-/Event-Vertrag.

## Rechte und Releasegrenze

Planung, Lehre und Veröffentlichung sind getrennte serverseitige Fähigkeiten.
Native Administration benötigt für fachlichen Vollzugriff eine app-lokale,
zeitlich begrenzte Freigabe. Die App bleibt bis zu den dokumentierten
Datenschutz-, Rechte-, Integrations- und Stagingnachweisen aus Bundles
ausgeschlossen.
