<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $controller = new App\Http\Controllers\Admin\DashboardController();
    $controller->index();
    echo "OK\n";
} catch (Throwable $e) {
    echo get_class($e)."\n";
    echo $e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}
