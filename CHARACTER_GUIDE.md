# Figuren für Moodle gestalten

Mika & Farn (w), Level 1, dient als Referenz für den Figurenstil und das in Moodle erprobte Thumbnail: [Avatar-Paket](packs/k4/mika-farn/level-1/mika-w.json).

## Figur und Level

Pro Figur werden eine stabile ID, Name, Beschreibung, Tags und Secret (Geheiminformation) festgelegt. Das Thema für NachbarRush wird direkt an der Figur als Tag hinterlegt: Abenteuer, Natur, Glitzer oder Action. Gesicht, Farben und charakteristische Merkmale dienen als visuelle Referenz für alle Bilder. Jedes weitere Level bekommt eine eigene Nummer unter derselben Figuren-ID.

| Motiv | Gültigkeit | Gestaltung |
|---|---|---|
| Galerievorschau (`preview`) | Figur | Wiedererkennbares Motiv, freigestellt als PNG mit transparentem Hintergrund. |
| Charakterszene (`additionalmedia`) | Figur | Die Figur handelt in einer Umgebung, die ihr Lernen oder eine Grundeigenschaft sichtbar macht. Ein gestalteter Hintergrund ist hier ausdrücklich erwünscht. Ein Maker kann in der Werkstatt tüfteln. Mika kann mit Farn im Wald eine Spur untersuchen. |
| Ganzkörperbild (`image`) | Level | Vollständige Figur mit typischer Haltung und Ausrüstung, freigestellt auf transparentem Hintergrund. |
| Thumbnail (`thumbnail`) | Level | Enges, ikonisches Porträt als transparentes PNG, 160 × 160 Pixel. Bei 40 × 40 Pixeln im Kreis prüfen: Gesicht und ein markantes Merkmal müssen lesbar bleiben. |
| Aktionspose(n) (`states`) | Level, optional | Die Figur zeigt eine klar lesbare Bewegung. Jede Pose einzeln als freigestelltes transparentes PNG liefern. |

## Bildprompt zum Wiederverwenden

> Zeichne [Figur] anhand des Referenzbildes als [Bildrolle] für Level [N]. Bewahre [Gesicht, Silhouette, Farben und Erkennungsmerkmal]. Zeige [Haltung oder konkrete Handlung]. Für Galerievorschau, Ganzkörperbild, Thumbnail und jede Aktionspose: Stelle das Motiv vollständig frei und liefere ein PNG mit echtem transparentem Hintergrund (Alphakanal). Vermeide Farbflächen, Hintergründe und eingezeichnete Schachbrettmuster. Für das Thumbnail: enger Gesichtsausschnitt, klare Kontur, ein Erkennungsmerkmal, lesbar im kleinen Kreis. Für die Charakterszene: Zeichne [konkrete Handlung] in [passender Umgebung]. Die Umgebung soll zeigen, wie die Figur lernt oder was sie auszeichnet.

## Prüfung vor der Freigabe

Freigestellte Motive auf hellem und dunklem Grund ansehen. Beim Thumbnail zusätzlich die kreisförmige Kleinansicht prüfen. Der Szenenname beschreibt die tatsächliche Handlung, etwa „Mika und Farn lesen Fuchsspuren“. Für neue Pakete kann die Szenendatei entsprechend `fuchsspuren.png` heissen. Bestehende Dateinamen können beibehalten werden.

Die technische Zuordnung der PNGs und JSON-Felder steht in der [Importanleitung](tools/Avatar-Import.md).
