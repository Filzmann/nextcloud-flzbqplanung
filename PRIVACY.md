# Datenschutzaufgaben für Dozentinnendaten

Der aktuelle Entwicklungsstand speichert personenbezogene Daten ausschließlich
für die Dozentinnenplanung:

- interne PFK: Nextcloud-UID;
- externe Dozentin: Name und E-Mail-Adresse;
- Bearbeitungsreferenzen: Nextcloud-UID der anlegenden oder ändernden Person;
- Zuordnungen: Haupt-PFK, Moduldozentin und Anfragezustand.

Bewerbungsakten, Teilnehmerinnenzuordnungen und Kommunikationsinhalte gehören
nicht in diese App.

Der app-eigene Processing-Katalog unter
`resources/privacy-processing.json` beschreibt diese Datenklassen und ihre
Verarbeitungsgrenzen ohne personenbezogene Laufzeitdaten. Der öffentliche
V1-Provider veröffentlicht ihn lazy an das Datenschutz-Center. Interne
Nextcloud-UIDs sind im bestehenden PersonalDataProvider abgedeckt; externe
Profile und Anfragen bleiben bis zu einem sicher authentifizierten externen
Subject-Vertrag ausdrücklich `PRIVACY-DECISION-REQUIRED` und werden nicht über
Name oder E-Mail identifiziert.

Vor der fachlichen Fertigstellung dieses Datenbereichs sind verpflichtend:

1. Der vorhandene app-eigene PersonalDataProvider über die öffentliche
   Datenschutz-Providergrenze liefert interne UID-Referenzen. Für externe
   Profile, Zuordnungen und Anfragen ist vor einer Ausgabe ein sicherer
   authentifizierter externer Subject-Vertrag festzulegen und zu testen.
2. Die Drittpersonensicht weist Bearbeitungsreferenzen und Dozentinnenbezüge in
   BQ-Durchläufen aus, ohne Bewerbungsdaten aus AD Recruitment zu lesen.
3. Offene oder bestätigte Anfragen und aktive BQ-Zuordnungen blockieren die
   Löschung eines externen Profils. Nach dem letzten fachlichen Bezug werden
   Name und E-Mail gemäß einer noch festzulegenden, konfigurierbaren Frist
   gelöscht oder anonymisiert; die Frist benötigt vor Umsetzung eine
   fachliche Freigabe.
4. Tests belegen Auskunft, Drittpersonenbezug, Retention-Blockade, Ablauf der
   freigegebenen Frist und das Ausbleiben app-fremder Datenzugriffe.

Bis diese Punkte umgesetzt und die Retention-Frist entschieden sind, ist der
Dozentinnenbereich ein lokaler Entwicklungsstand und nicht releasefähig.
