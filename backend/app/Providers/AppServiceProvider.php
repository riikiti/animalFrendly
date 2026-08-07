<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\VKontakte\Provider as VKontakteProvider;

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
        // Драйвер VK живёт в отдельном пакете и подключается событием менеджера Socialite.
        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('vkontakte', VKontakteProvider::class);
        });

        // Ответы API никогда не оборачиваются в ключ "data" — единичные ресурсы
        // и ресурсы внутри массива ведут себя одинаково.
        JsonResource::withoutWrapping();
    }
}
