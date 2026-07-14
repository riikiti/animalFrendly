<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

foreach (glob(app_path('Modules/*/Presentation/routes.php')) as $moduleRoutes) {
    require $moduleRoutes;
}
