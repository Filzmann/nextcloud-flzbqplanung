# Datenschutzaufgaben für Dozentinnendaten

Der aktuelle Entwicklungsstand speichert personenbezogene Daten für die
Dozentinnen- und BQ-Planung:

- interne PFK: Nextcloud-UID sowie ein nur für die Gruppensuche transient
  ausgegebener Nextcloud-Anzeigename, der nicht zusätzlich im Profil
  persistiert wird;
- externe Dozentin: Name und E-Mail-Adresse;
- Bearbeitungsreferenzen: Nextcloud-UID der anlegenden oder ändernden Person
  an Dozentinnenprofilen, der zentralen Curriculum-Vorlage und
  Durchlauf-Snapshots;
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
   BQ-Durchläufen aus, ohne Bewerbungsdaten aus Filzmann Recruitment zu lesen.
3. Für externe Profile ist die Zwölfmonatsregel als Policy beschlossen. Offene
   oder bestätigte Anfragen und aktive BQ-Zuordnungen blockieren ihre
   Ausführung. Die automatische Löschung bleibt bis zum vollständigen
   app-lokalen Lösch-, Restore-, Referenzintegritäts-, Nebenläufigkeits- und
   Fehlernachweis deaktiviert.
4. Tests belegen Auskunft, Drittpersonenbezug, Retention-Blockade, Ablauf der
   freigegebenen Frist und das Ausbleiben app-fremder Datenzugriffe.

Bis diese Punkte vollständig umgesetzt und nachgewiesen sind, ist der
Dozentinnenbereich ein lokaler Entwicklungsstand und nicht releasefähig.
