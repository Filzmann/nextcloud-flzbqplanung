# Roadmap – AD BQ-Planer

Diese Datei enthält ausschließlich offene app-lokale Produktaufgaben und
Freigabegates. Der implementierte Umfang steht in `README.md`; geltende
Fach- und Architekturgrenzen stehen in `AGENTS.md`.

## Nextcloud-Kompatibilitätsgate

### BQ-NC-COMPAT – OpenDesk-Boden 33 und künftige Majors nachweisen

Status: `info.xml` bleibt bei 34/34. Vor einer Absenkung muss der bereits auf
NC 33 und 34 ungültige Import `OCP\Http` app-lokal test-first durch
`OCP\AppFramework\Http` ersetzt werden. Danach sind Fresh Install/Upgrade,
DI, Migrationen, Rollen-/Adminschutz, Vorschlags- und Curriculumabläufe,
LocalBase-Kalenderprovider einschließlich Ausfall, Privacy-/PermissionProvider,
Assets und sichtbare Oberfläche auf NC 33 zu prüfen. Die Obergrenze wird je
Major lückenlos mit `verify-nextcloud-future-compatibility` bestimmt.

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
