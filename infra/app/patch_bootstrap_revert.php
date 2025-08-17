<?php
$f = 'bootstrap/app.php';
$c = file_get_contents($f);
if ($c === false) { fwrite(STDERR, "cannot read $f\n"); exit(1); }
$orig = $c;

/* убрать use App\Http\Middleware\LogDedup; (если был добавлен) */
$c = preg_replace('/^\s*use\s+App\\\\Http\\\\Middleware\\\\LogDedup;\s*\n/m', '', $c, -1);

/* убрать строку $middleware->appendToGroup('api', LogDedup::class); */
$c = preg_replace('/^\s*\$middleware->appendToGroup\(\'api\',\s*LogDedup::class\);\s*\n/m', '', $c, -1);

/* также на всякий случай в двойных кавычках */
$c = preg_replace('/^\s*\$middleware->appendToGroup\("api",\s*LogDedup::class\);\s*\n/m', '', $c, -1);

if ($c !== $orig) {
  file_put_contents($f, $c);
  echo "patched\n";
} else {
  echo "no_change\n";
}
