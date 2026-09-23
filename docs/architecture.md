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
zeitlich begrenzte Freigabe. Ausschließlich Mitglieder der nativen Gruppe
`Datenschutzbeauftragte` dürfen die Freigabehistorie lesen und Freigaben für
aktuelle native Administrationskonten in der BQ-Fachoberfläche erteilen oder
widerrufen. Die authentifizierten Endpunkte sind deshalb nicht an die native
Adminroute gebunden; Schreibrequests bleiben CSRF-geschützt und prüfen Rolle,
Zielkonto und Dauer serverseitig. Rollenverlust, ungültige Ziele,
Persistenzfehler, Ablauf und Verlust des nativen Adminstatus verweigern ohne
zusätzliche Fachrechte. Ein fehlender Vollzugriff wird ausschließlich dem
betroffenen Administrationskonto angezeigt; der direkte Sprung zur Steuerung
erscheint nur beim selben Konto mit zusätzlicher Datenschutzrolle. Die App bleibt bis zu den dokumentierten
Datenschutz-, Rechte-, Integrations- und Stagingnachweisen aus Bundles
ausgeschlossen.

## Processing-Metadaten

`resources/privacy-processing.json` ist die einzige app-eigene Policyquelle
für `bq_run_curriculum_and_schedule_management`,
`lecturer_profile_and_assignment_management`,
`external_lecturer_request_tracking` und `temporary_admin_full_access`. Der
öffentliche V1-Provider von `filzmann_data_protection` lädt den Katalog lazy
und veröffentlicht keine personenbezogenen Laufzeitdaten.

Die Trennung folgt dem vorhandenen Modell: Durchläufe und Module halten
Termin- und Curriculum-Snapshots samt interner Bearbeitungsreferenzen;
Dozentinnenprofile unterscheiden geprüfte interne Nextcloud-UIDs von
minimalen externen Name-/E-Mail-Profilen; Anfragen dokumentieren nur den
außerhalb der App erfolgten Kommunikationsstatus und speichern weder
Nachrichten noch Bewerbungs- oder Teilnehmerdaten. Die Adminfreigabe bleibt
eine eigene, UID-genaue und höchstens 24 Stunden wirksame Verarbeitung.

Der PersonalDataProvider liefert interne PFK- und Bearbeitungsbezüge. Externe
Profile und Anfragen bleiben bis zu einem authentifizierten externen
Subject-Vertrag ausgeschlossen; Name oder E-Mail sind keine Identifikation.
Rechtsgrundlagen, Retention, Backup/Restore, externer Kommunikationskanal und
Lösch-/Anonymisierungsmaßnahmen werden im Katalog sichtbar
`PRIVACY-DECISION-REQUIRED` gehalten.
