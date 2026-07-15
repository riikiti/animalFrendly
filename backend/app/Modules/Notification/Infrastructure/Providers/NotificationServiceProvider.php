<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Providers;

use App\Modules\Chat\Domain\Events\MessageSent;
use App\Modules\Marketplace\Domain\Events\OrderCompleted;
use App\Modules\Matching\Domain\Events\PetsMatched;
use App\Modules\Notification\Application\Contracts\FcmClientInterface;
use App\Modules\Notification\Domain\Repositories\DeviceTokenRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Modules\Notification\Infrastructure\External\FcmClient;
use App\Modules\Notification\Infrastructure\External\NullFcmClient;
use App\Modules\Notification\Infrastructure\Listeners\NotifyOnAdoptionRequestApproved;
use App\Modules\Notification\Infrastructure\Listeners\NotifyOnMessageSent;
use App\Modules\Notification\Infrastructure\Listeners\NotifyOnOrderCompleted;
use App\Modules\Notification\Infrastructure\Listeners\NotifyOnPetsMatched;
use App\Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories\EloquentDeviceTokenRepository;
use App\Modules\Notification\Infrastructure\Persistence\Eloquent\Repositories\EloquentNotificationRepository;
use App\Modules\Shelter\Domain\Events\AdoptionRequestApproved;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(NotificationRepositoryInterface::class, EloquentNotificationRepository::class);
        $this->app->bind(DeviceTokenRepositoryInterface::class, EloquentDeviceTokenRepository::class);

        // Фоллбэк для локальной разработки, когда реальных FCM-ключей ещё нет — тот же
        // принцип, что YookassaClientInterface в PaymentServiceProvider.
        $this->app->bind(
            FcmClientInterface::class,
            empty(config('fcm.project_id')) ? NullFcmClient::class : FcmClient::class,
        );
    }

    public function boot(): void
    {
        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->listen(PetsMatched::class, NotifyOnPetsMatched::class);
        $dispatcher->listen(MessageSent::class, NotifyOnMessageSent::class);
        $dispatcher->listen(AdoptionRequestApproved::class, NotifyOnAdoptionRequestApproved::class);
        $dispatcher->listen(OrderCompleted::class, NotifyOnOrderCompleted::class);
    }
}
