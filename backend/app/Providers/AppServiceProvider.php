<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ответы API никогда не оборачиваются в ключ "data" — единичные ресурсы
        // и ресурсы внутри массива ведут себя одинаково.
        JsonResource::withoutWrapping();
    }
}
