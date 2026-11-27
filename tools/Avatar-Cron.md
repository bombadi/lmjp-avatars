# Avatar-Pakete über Cron veröffentlichen

Der VPS liest alle fünf Minuten den aktuellen Commit von origin/main im vorhandenen Git-Repository /opt/lmjp-avatars. Automatisch importierte Pakete liegen unter packs/ und heissen paket.json oder enden auf .avatar.json. Für den ersten Test ist auch packs/k4/mika-farn/level-1/mika-w.json zugelassen. Die Paketfelder sind in Avatar-Import.md beschrieben.

Nach dem Merge auf dem VPS installieren:

```bash
sudo git -C /opt/lmjp-avatars pull --ff-only &&
sudo sh /opt/lmjp-avatars/tools/install-avatar-cron.sh
```

Voraussetzungen sind der funktionierende nichtinteraktive Git-Zugriff als root, der Ubuntu-Dienst cron sowie die vorhandene Moodle-Konfiguration unter /var/www/moodle/config.php. Installer und PHP-Importer werden aus dem lokal geprüften Stand installiert. Spätere Code-Updates benötigen einen erneuten Installationslauf. Die Abrufe verwenden einen separaten lokalen Git-Ref und verändern den Checkout nicht.

Manuell auslösen: sudo /usr/local/lib/lmjp-avatars/sync-avatars.py

Protokoll: sudo journalctl -t lmjp-avatar-sync --since today

Pausieren: sudo mv /etc/cron.d/lmjp-avatar-sync /etc/cron.d/.lmjp-avatar-sync.paused

Pro Avatar-ID und Level wird der letzte erfolgreich importierte Paketstand in /var/lib/lmjp-avatar-sync gespeichert. Geänderte JSON- oder Bildinhalte lösen einen Import aus. Nach einem Fehler bleiben spätere Level derselben Figur bis zum nächsten erfolgreichen Versuch zurückgestellt. Entfernte Pakete löschen keine Moodle-Daten.
