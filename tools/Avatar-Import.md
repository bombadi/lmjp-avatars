# Avatar-Pakete importieren

Ein Paket beschreibt genau eine Figur und ein nummeriertes Level. Die JSON-Datei und ihre PNGs liegen im selben Verzeichnis im Repository. Die stabile ID entscheidet, welcher Moodle-Figur das Paket zugeordnet wird.

Beispiel für ein vollständiges Paket (Dateinamen durch tatsächliche Bilder ersetzen):

```json
{
  "id": "lmjp-k4-mika-farn-w",
  "level": 1,
  "name": "Mika & Farn",
  "description": "Mika begegnet einem neugierigen Fuchs.",
  "secret": "Farn macht den ersten Schritt mit der weissen Pfote.",
  "tags": ["Klasse 4", "Natur", "Weiblich"],
  "preview": "galerie.png",
  "additionalmedia": ["werkstatt.png"],
  "image": "ganzkoerper.png",
  "thumbnail": "profil.png",
  "states": ["aktion.png"]
}
```

Für eine vorhandene Figur genügen ID, Level, Ganzkörperbild und Thumbnail. Weitere Aktionsbilder sind optional:

```json
{
  "id": "lmjp-k4-mika-farn-w",
  "level": 2,
  "image": "level-2.png",
  "thumbnail": "level-2-profil.png",
  "states": ["level-2-aktion.png"]
}
```

Auf dem VPS zuerst ohne Schreibzugriff auf die Avatardaten prüfen:

```bash
cd /opt/lmjp-avatars
sudo -u www-data php tools/import-avatar.php /var/www/moodle/config.php PFAD/ZUM/paket.json
```

Nach Prüfung denselben Befehl mit `--apply` am Ende ausführen. Ein bestehendes Level lässt sich mit derselben Nummer aktualisieren. Ein neues Level muss direkt an das letzte anschliessen. Bei vorhandenen Figuren werden Name, Beschreibung, Secret und Tags beibehalten. Ein mitgeliefertes Galeriebild ersetzt die bisherige Vorschau. Zusätzliche Medien und Aktionsbilder werden nach Dateiname ergänzt oder aktualisiert. Andere Dateien in diesen beiden Bereichen bleiben erhalten.

Die drei ursprünglichen Mika-Testfiguren können so einzeln über ihre bestehenden IDs aktualisiert werden. Der alte Testimporter prüft die ursprünglichen Bild-Hashes und meldet nach einem Bildwechsel erwartungsgemäss eine Abweichung.
