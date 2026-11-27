#!/usr/bin/python3
"""Synchronise released Moodle avatar packages from origin/main."""
import fcntl
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import subprocess
import sys
import tempfile

REPO = Path('/opt/lmjp-avatars')
STATE = Path('/var/lib/lmjp-avatar-sync')
IMPORTER = '/usr/local/lib/lmjp-avatars/import-avatar.php'
CONFIG = '/var/www/moodle/config.php'


def git(*args):
    return subprocess.check_output(['git', '-C', REPO, *args])


def import_package(directory, manifest):
    subprocess.run(['runuser', '-u', 'www-data', '--', '/usr/bin/php', IMPORTER,
                    CONFIG, str(directory / manifest), '--apply'], check=True)


def image_names(data):
    names = [data['image'], data['thumbnail']]
    if 'preview' in data:
        names.append(data['preview'])
    for key in ('additionalmedia', 'states'):
        extra = data.get(key, [])
        if not isinstance(extra, list):
            raise ValueError(f'{key}: Dateiliste erwartet')
        names.extend(extra)
    for name in names:
        if not isinstance(name, str) or not re.fullmatch(r'[A-Za-z0-9_-][A-Za-z0-9_.-]*\.png', name, re.I):
            raise ValueError(f'Ungültiger Bildname: {name!r}')
    return sorted(set(names))


def sync():
    git('fetch', '--quiet', '--no-tags', '--no-recurse-submodules', 'origin',
        '+refs/heads/main:refs/lmjp-avatar-sync/main')
    commit = git('rev-parse', 'refs/lmjp-avatar-sync/main').decode().strip()
    entries = {}
    for line in git('ls-tree', '-rz', commit, '--', 'packs/').split(b'\0'):
        if line:
            meta, path = line.split(b'\t', 1)
            mode, _, oid = meta.decode().split()
            entries[PurePosixPath(os.fsdecode(path))] = (mode, oid)

    def blob(path):
        mode, oid = entries[path]
        if mode not in ('100644', '100755'):
            raise ValueError(f'Keine reguläre Datei: {path}')
        return oid

    packages = {}
    for path in sorted(entries):
        # Only explicitly released package manifests participate in automatic import.
        if not (path.name.endswith('.avatar.json') or path.name == 'paket.json'
                or str(path) == 'packs/k4/mika-farn/level-1/mika-w.json'):
            continue
        oid = blob(path)
        data = json.loads(git('cat-file', 'blob', oid))
        key = (data['id'], data['level'])
        if not isinstance(key[0], str) or not key[0].strip() or type(key[1]) is not int or not 1 <= key[1] <= 10:
            raise ValueError(f'Ungültige ID oder Stufe: {path}')
        if key in packages:
            raise ValueError(f'Doppelte ID und Stufe: {path}, {packages[key][0]}')
        packages[key] = (path, data, oid)

    failed = set()
    skipped = 0
    for (avatar, level), (path, data, oid) in sorted(packages.items()):
        if avatar in failed:
            continue
        try:
            sources = {name: blob(path.parent / name) for name in image_names(data)}
            sources[path.name] = oid
            signature = hashlib.sha256(json.dumps(sources, sort_keys=True).encode()).hexdigest()
            key = hashlib.sha256(json.dumps([avatar, level]).encode()).hexdigest()
            marker = STATE / key
            if marker.exists() and marker.read_text() == signature:
                skipped += 1
                continue
            print(f'{commit[:12]} {avatar} Level {level}: Import', flush=True)
            with tempfile.TemporaryDirectory(prefix='lmjp-avatar-', dir='/tmp') as tmp:
                directory = Path(tmp)
                for name, fileoid in sources.items():
                    with (directory / name).open('wb') as out:
                        subprocess.run(['git', '-C', REPO, 'cat-file', 'blob', fileoid], stdout=out, check=True)
                    (directory / name).chmod(0o644)
                directory.chmod(0o711)
                import_package(directory, path.name)
            pending = marker.with_suffix('.tmp')
            pending.write_text(signature)
            pending.replace(marker)
        except (OSError, KeyError, ValueError, subprocess.CalledProcessError) as error:
            failed.add(avatar)
            print(f'{path}: {error}', file=sys.stderr, flush=True)
    print(f'{len(packages)} Pakete, {skipped} unverändert, {len(failed)} Figuren mit Fehler.')
    return int(bool(failed))


def main():
    STATE.mkdir(mode=0o700, parents=True, exist_ok=True)
    with (STATE / 'lock').open('a') as lock:
        try:
            fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
        except BlockingIOError:
            print('Ein Abgleich läuft bereits.')
            return 0
        return sync()


if __name__ == '__main__':
    try:
        sys.exit(main())
    except (OSError, ValueError, KeyError, TypeError, subprocess.CalledProcessError) as exc:
        sys.exit(f'Avatar-Abgleich fehlgeschlagen: {exc}')
