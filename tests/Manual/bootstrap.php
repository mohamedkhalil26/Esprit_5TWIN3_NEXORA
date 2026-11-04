<?php

use Illuminate\Contracts\Console\Kernel;

// Instance de recette jetable : aucun accès à la base MySQL du projet.
$root = dirname(__DIR__, 2);
$work = $root.'/storage/app/private/recette-finale';
foreach (['', '/runtime/framework/cache/data', '/runtime/framework/sessions', '/runtime/framework/views', '/runtime/logs', '/runtime/app/private', '/runtime/app/public'] as $suffix) {
    if (! is_dir($work.$suffix)) {
        mkdir($work.$suffix, 0700, true);
    }
}
foreach (['APP_ENV' => 'local', 'APP_DEBUG' => 'false', 'APP_URL' => 'http://127.0.0.1:8010', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $work.'/recette.sqlite', 'DB_URL' => '', 'SESSION_DRIVER' => 'file', 'SESSION_COOKIE' => 'solarshare_recette', 'CACHE_STORE' => 'file', 'QUEUE_CONNECTION' => 'sync', 'MAIL_MAILER' => getenv('SOLARSHARE_RECETTE_SMTP') === '1' ? 'smtp' : 'log', 'LOG_CHANNEL' => 'single'] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->useStoragePath($work.'/runtime');
$app->make(Kernel::class)->bootstrap();
if ($app->configurationIsCached() || config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== $work.'/recette.sqlite') {
    throw new RuntimeException('Isolation de recette non garantie : arrêt.');
}
config(['logging.channels.single.path' => $work.'/runtime/logs/laravel.log']);

return $app;
