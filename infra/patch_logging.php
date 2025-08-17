<?php
$f = 'config/logging.php';
$c = file_get_contents($f);
if ($c === false) { fwrite(STDERR, "cannot read $f\n"); exit(1); }

if (strpos($c, "'lead_dedup'") === false) {
    // Вставим в секцию 'channels' => [ ... ]
    $c = preg_replace(
        '/(\'channels\'\s*=>\s*\[)/',
        "$1\n        'lead_dedup' => [\n"
        ."            'driver' => 'daily',\n"
        ."            'path'   => storage_path('logs/lead-dedup.log'),\n"
        ."            'level'  => 'info',\n"
        ."            'days'   => 14,\n"
        ."        ],\n",
        $c,
        1,
        $cnt
    );
    if (!$cnt) { fwrite(STDERR, "could not patch channels array\n"); exit(1); }
    file_put_contents($f, $c);
    echo "patched\n";
} else {
    echo "already\n";
}
