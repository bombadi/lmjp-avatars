<?php
// Read-only Moodle avatar import preflight.
if (PHP_SAPI !== 'cli') {
    exit(1);
}
define('CLI_SCRIPT', true);
$config = $argv[1] ?? '';
if (!is_file($config) || !is_readable($config)) {
    fwrite(STDERR, "Aufruf: php check-moodle.php /pfad/zu/moodle/config.php\n");
    exit(1);
}
require $config;

if (!is_callable([\mod_avatar\avatar_manager::class, 'create_avatar'])
        || !method_exists(\mod_avatar\avatar::class, 'save_form_files')
        || !defined(\mod_avatar\avatar::class . '::MAX_VARIANTS')) {
    fwrite(STDERR, "Die erwartete Avatar-Schnittstelle ist nicht verfügbar.\n");
    exit(1);
}

echo "Avatar-Schnittstelle geladen. Maximale Level pro Figur: ",
    \mod_avatar\avatar::MAX_VARIANTS, "\n";

$occupied = false;
foreach (['w', 'm', 'a'] as $presentation) {
    $idnumber = 'lmjp-k4-mika-farn-' . $presentation;
    $count = $DB->count_records('avatar_list', ['idnumber' => $idnumber]);
    echo $idnumber, ': ', $count === 0 ? 'frei' : "belegt ($count)", "\n";
    $occupied = $occupied || $count > 0;
}
exit($occupied ? 2 : 0);
