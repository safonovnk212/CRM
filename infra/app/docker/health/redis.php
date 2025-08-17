<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $pong = Illuminate\Support\Facades\Redis::ping();
    $ok = $pong === true
        || $pong === '+PONG'
        || $pong === 'PONG'
        || (is_string($pong) && stripos($pong, 'PONG') !== false);
    if (!$ok) {
        fwrite(STDERR, "[hc] unexpected ping result: ".var_export($pong, true).PHP_EOL);
    }
    exit($ok ? 0 : 2);
} catch (Throwable $e) {
    fwrite(STDERR, "[hc] exception: ".$e->getMessage().PHP_EOL);
    exit(1);
}
