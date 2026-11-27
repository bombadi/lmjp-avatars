<?php
// Import a single numbered avatar level. Without --apply, only validate and report.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
define('CLI_SCRIPT', true);

function package_text(array $package, string $key): string {
    if (!is_string($package[$key] ?? null) || trim($package[$key]) === '') {
        throw new RuntimeException("Textfeld fehlt: $key");
    }
    return trim($package[$key]);
}

function package_images(array $package, string $dir, int $level): array {
    $areas = ["avatarimage$level" => [package_text($package, 'image')],
        "avatarthumbnail$level" => [package_text($package, 'thumbnail')]];
    if (isset($package['preview'])) {
        $areas['previewimage'] = [package_text($package, 'preview')];
    }
    foreach (['additionalmedia' => 'additionalmedia', 'states' => "animationstates$level"] as $key => $area) {
        if (array_key_exists($key, $package)) {
            if (!is_array($package[$key]) || !array_is_list($package[$key])) {
                throw new RuntimeException("$key muss eine Dateiliste sein.");
            }
            $areas[$area] = $package[$key];
        }
    }
    $files = [];
    foreach ($areas as $area => $names) {
        foreach ($names as $name) {
            if (!is_string($name) || !preg_match('/^[A-Za-z0-9_-][A-Za-z0-9_.-]*\.png$/iD', $name)
                    || isset($files[$area][$name])) {
                throw new RuntimeException("Ungültiger oder doppelter PNG-Dateiname in $area");
            }
            $path = realpath($dir . '/' . $name);
            if (!$path || dirname($path) !== $dir || !is_file($path) || !is_readable($path)
                    || (@getimagesize($path)[2] ?? null) !== IMAGETYPE_PNG) {
                throw new RuntimeException("PNG fehlt oder ist ungültig: $name");
            }
            $files[$area][$name] = $path;
        }
    }
    return $files;
}

try {
    if (!in_array($argc, [3, 4], true) || ($argc === 4 && $argv[3] !== '--apply')) {
        throw new RuntimeException("Aufruf: php import-avatar.php MOODLE_CONFIG PAKET_JSON [--apply]");
    }
    $apply = $argc === 4;
    $manifest = realpath($argv[2]);
    if (!$manifest || !is_readable($manifest)) {
        throw new RuntimeException('Paketdatei fehlt.');
    }
    $package = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
    $keys = ['id', 'level', 'image', 'thumbnail', 'name', 'description', 'secret', 'tags',
        'preview', 'additionalmedia', 'states'];
    if (!is_array($package) || array_diff(array_keys($package), $keys)) {
        throw new RuntimeException('Paket enthält unbekannte Felder oder ist kein JSON-Objekt.');
    }
    $idnumber = package_text($package, 'id');
    $level = $package['level'] ?? null;
    if (!is_int($level) || $level < 1 || $level > 10) {
        throw new RuntimeException('level muss eine ganze Zahl von 1 bis 10 sein.');
    }
    $files = package_images($package, dirname($manifest), $level);
    require $argv[1];
    \core\session\manager::set_user(get_admin());
    $lock = \core\lock\lock_config::get_lock_factory('lmjp_avatars')->get_lock('import', 15);
    if (!$lock) {
        throw new RuntimeException('Ein anderer Import läuft.');
    }
    try {
        $transaction = $DB->start_delegated_transaction();
        try {
            $avatar = $DB->get_record('avatar_list', ['idnumber' => $idnumber], '*', IGNORE_MISSING);
            if ($avatar && $DB->count_records('avatar_list', ['idnumber' => $idnumber]) !== 1) {
                throw new RuntimeException("Mehrdeutige Kennung: $idnumber");
            }
            $count = $avatar ? (int)$avatar->variants : 0;
            if ($level > $count + 1) {
                throw new RuntimeException("Level übersprungen: bisher $count, angefordert $level.");
            }
            $id = $avatar->id ?? null;
            if (!$avatar) {
                if (empty($files['previewimage']) || empty($files['additionalmedia'])) {
                    throw new RuntimeException('Neue Figur benötigt Galerievorschau und Charakterszene.');
                }
                $tags = $package['tags'] ?? null;
                if (!is_array($tags) || !array_is_list($tags) || !$tags) {
                    throw new RuntimeException('tags muss eine nichtleere Liste sein.');
                }
                foreach ($tags as $tag) {
                    if (!is_string($tag) || trim($tag) === '') {
                        throw new RuntimeException('Ungültiger Tag.');
                    }
                }
                $data = (object)['idnumber' => $idnumber, 'variants' => 1, 'tags' => $tags,
                    'name' => package_text($package, 'name'), 'description' => package_text($package, 'description'),
                    'secretinfo' => package_text($package, 'secret'), 'descriptionformat' => FORMAT_PLAIN,
                    'secretinfoformat' => FORMAT_PLAIN];
                echo "Neue Figur: $idnumber\n";
                if ($apply) {
                    $id = \mod_avatar\avatar_manager::create_avatar($data);
                }
            } else {
                echo "Bestehende Figur: $idnumber (Level $count vorhanden)\n";
                if ($level > $count && $apply) {
                    $DB->set_field('avatar_list', 'variants', $level, ['id' => $id]);
                }
            }
            $fs = get_file_storage();
            $contextid = \context_system::instance()->id;
            foreach ($files as $area => $entries) {
                $single = in_array($area, ['previewimage', "avatarimage$level", "avatarthumbnail$level"], true);
                $oldfiles = $id ? $fs->get_area_files($contextid, 'mod_avatar', $area, $id, 'id', false) : [];
                foreach ($entries as $name => $path) {
                    $same = false;
                    $replace = false;
                    foreach ($oldfiles as $old) {
                        $match = $old->get_filepath() === '/' && $old->get_filename() === $name;
                        if ($match && $old->get_contenthash() === sha1_file($path)) {
                            $same = true;
                        } else if ($single || $match) {
                            $replace = true;
                            if ($apply) {
                                $old->delete();
                            }
                        }
                    }
                    $action = $replace ? 'ersetzen' : ($same ? 'vorhanden' : 'hinzufügen');
                    echo "$area: $name ($action)\n";
                    if ($apply && !$same) {
                        $fs->create_file_from_pathname(['contextid' => $contextid, 'component' => 'mod_avatar',
                            'filearea' => $area, 'itemid' => $id, 'filepath' => '/', 'filename' => $name], $path);
                    }
                }
            }
            $transaction->allow_commit();
            echo $apply ? "Import abgeschlossen.\n" : "Prüflauf abgeschlossen. Zum Import --apply ergänzen.\n";
        } catch (Throwable $error) {
            $transaction->rollback($error);
        }
    } finally {
        $lock->release();
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
