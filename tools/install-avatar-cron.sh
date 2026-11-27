#!/bin/sh
set -eu
if [ "$(id -u)" != 0 ]; then
    echo "Bitte mit sudo ausführen." >&2
    exit 1
fi
tools_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
target=/usr/local/lib/lmjp-avatars
/usr/bin/php -l "$tools_dir/import-avatar.php"
/usr/bin/python3 -c 'import ast,sys; ast.parse(open(sys.argv[1]).read())' "$tools_dir/sync-avatars.py"
install -d -m 755 "$target"
install -m 644 "$tools_dir/import-avatar.php" "$target/import-avatar.php"
install -m 755 "$tools_dir/sync-avatars.py" "$target/sync-avatars.py"
systemctl enable --now cron
tmpcron=$(mktemp /etc/cron.d/.lmjp-avatar-sync.XXXXXX)
trap 'rm -f "$tmpcron"' EXIT
cat > "$tmpcron" <<'CRON'
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
GIT_TERMINAL_PROMPT=0
*/5 * * * * root /usr/bin/systemd-cat -t lmjp-avatar-sync /usr/local/lib/lmjp-avatars/sync-avatars.py
CRON
chmod 644 "$tmpcron"
mv -f "$tmpcron" /etc/cron.d/lmjp-avatar-sync
echo 'Cronjob aktiv. Protokoll: journalctl -t lmjp-avatar-sync'
