<?php
// Install the three original level-one avatars in one transaction.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
define('CLI_SCRIPT', true);
if ($argc !== 2 || !is_readable($argv[1])) {
    fwrite(STDERR, "Aufruf: php import-mika.php /pfad/zu/moodle/config.php\n");
    exit(1);
}
require $argv[1];
\core\session\manager::set_user(get_admin());

$context = \context_system::instance();
$fs = get_file_storage();
$dir = __DIR__ . '/../packs/k4/mika-farn/level-1';
$lock = \core\lock\lock_config::get_lock_factory('lmjp_avatars')->get_lock('import', 15);
if (!$lock) {
    fwrite(STDERR, "Ein anderer Import läuft.\n");
    exit(1);
}
try {
    $transaction = $DB->start_delegated_transaction();
    try {
        $messages = [];
        foreach (['w' => 'Weiblich', 'm' => 'Männlich', 'a' => 'Androgyn'] as $key => $tag) {
            $idnumber = 'lmjp-k4-mika-farn-' . $key;
            $files = ['previewimage' => "$key-thumb.png", 'avatarimage1' => "$key-avatar.png",
                'avatarthumbnail1' => "$key-thumb.png"];
            $record = $DB->get_record('avatar_list', ['idnumber' => $idnumber], '*', IGNORE_MISSING);
            if ($record && $DB->count_records('avatar_list', ['idnumber' => $idnumber]) !== 1) {
                throw new RuntimeException("Mehrdeutige Kennung: $idnumber");
            }
            $id = $record->id ?? null;
            if (!$record) {
                $id = \mod_avatar\avatar_manager::create_avatar((object) [
                    'name' => 'Mika & Farn', 'idnumber' => $idnumber, 'variants' => 1,
                    'tags' => ['Klasse 4', 'Natur', $tag],
                    'description' => 'Mitten auf dem Waldweg bleibt Mika stehen. Aus dem Laub ragt eine rostrote Schwanzspitze. Mika wartet. Kurz darauf sitzt ein kleiner Fuchs auf Mikas Schuh, als hätte er genau diesen Platz reserviert.',
                    'descriptionformat' => FORMAT_PLAIN,
                    'secretinfo' => 'Farn hat zwei verschiedenfarbige Vorderpfoten. Wenn er sich etwas traut, macht er den ersten Schritt immer mit der weissen.',
                    'secretinfoformat' => FORMAT_PLAIN,
                ]);
            }
            foreach ($files as $area => $name) {
                $path = "$dir/$name";
                if (!is_readable($path) || (getimagesize($path)[2] ?? null) !== IMAGETYPE_PNG) {
                    throw new RuntimeException("Ungültiges Paketbild: $name");
                }
                if ($record) {
                    $file = $fs->get_file($context->id, 'mod_avatar', $area, $id, '/', $name);
                    if (!$file || $file->get_contenthash() !== sha1_file($path)) {
                        throw new RuntimeException("Kennung belegt mit abweichenden Bildern: $idnumber ($area)");
                    }
                } else {
                    $fs->create_file_from_pathname([
                        'contextid' => $context->id, 'component' => 'mod_avatar', 'filearea' => $area,
                        'itemid' => $id, 'filepath' => '/', 'filename' => $name,
                    ], $path);
                }
            }
            $messages[] = $idnumber . ($record ? ' bereits vorhanden' : ' angelegt');
        }
        $transaction->allow_commit();
        echo implode("\n", $messages), "\n";
    } catch (Throwable $error) {
        $transaction->rollback($error);
    }
} finally {
    $lock->release();
}
