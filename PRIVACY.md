# Datenschutzaufgaben für Dozentinnendaten

Der aktuelle Entwicklungsstand speichert personenbezogene Daten ausschließlich
für die Dozentinnenplanung:

- interne PFK: Nextcloud-UID;
- externe Dozentin: Name und E-Mail-Adresse;
- Bearbeitungsreferenzen: Nextcloud-UID der anlegenden oder ändernden Person;
- Zuordnungen: Haupt-PFK, Moduldozentin und Anfragezustand.

Bewerbungsakten, Teilnehmerinnenzuordnungen und Kommunikationsinhalte gehören
nicht in diese App.

Vor der fachlichen Fertigstellung dieses Datenbereichs sind verpflichtend:

1. Ein app-eigener PersonalDataProvider über die öffentliche
   Datenschutz-Providergrenze liefert interne UID-Referenzen sowie externe
   Profile, Zuordnungen und Anfragen für die betroffene Person aus.
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
