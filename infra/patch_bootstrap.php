<?php
$f = 'bootstrap/app.php';
$c = file_get_contents($f);
$changed = false;

/* 1) Добавим use App\Http\Middleware\LogDedup; сразу после <?php */
if (strpos($c, "use App\\Http\\Middleware\\LogDedup;") === false) {
    $c = preg_replace('/^<\?php\s*/', "<?php\nuse App\\Http\\Middleware\\LogDedup;\n", $c, 1, $cnt);
    $changed = $changed || ($cnt > 0);
}

/* 2) Вставим $middleware->appendToGroup('api', LogDedup::class); в withMiddleware(...) */
if (strpos($c, "appendToGroup('api', LogDedup::class)") === false &&
    strpos($c, 'appendToGroup("api", LogDedup::class)') === false) {

    // Попытка №1: вставить сразу после '{' блока withMiddleware
    $c2 = preg_replace(
        '/(->withMiddleware\(\s*function\s*\(Middleware\s+\$middleware\)\s*:\s*void\s*\{\s*)/s',
        "$1        \$middleware->appendToGroup('api', LogDedup::class);\n",
        $c, 1, $cnt1
    );

    if ($cnt1 === 0) {
        // Попытка №2: общий фоллбэк — перед закрывающей скобкой блока
        $c2 = preg_replace(
            '/(->withMiddleware\(\s*function\s*\(Middleware\s+\$middleware\)\s*:\s*void\s*\{)([\s\S]*?\n\s*\})/s',
            "$1$2\n        \$middleware->appendToGroup('api', LogDedup::class);\n",
            $c, 1, $cnt2
        );
    }

    if (!empty($c2)) {
        $c = $c2;
        $changed = $changed || ($cnt1 > 0 || $cnt2 > 0);
    }
}

file_put_contents($f, $c);
echo $changed ? "patched\n" : "no_change\n";
