<?php

declare(strict_types=1);

use App\Modules\Notification\Application\Contracts\UserEmailLookupInterface;
use App\Modules\Notification\Application\Services\NotificationDispatcher;
use App\Modules\Notification\Domain\Entities\DeviceToken;
use App\Modules\Notification\Domain\Entities\Notification;
use App\Modules\Notification\Domain\Enums\DevicePlatform;
use App\Modules\Notification\Domain\Enums\NotificationType;
use App\Modules\Notification\Domain\Repositories\DeviceTokenRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Modules\Notification\Infrastructure\Jobs\SendPushNotificationJob;
use App\Modules\Notification\Infrastructure\Mail\NotificationMail;
use App\Shared\Domain\ValueObjects\Id;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

it('creates and saves a notification for the target user', function (): void {
    $userId = Id::generate();

    $notifications = Mockery::mock(NotificationRepositoryInterface::class);
    $notifications->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $notifications->shouldReceive('save')->once()->with(Mockery::on(
        fn (Notification $n) => $n->userId()->equals($userId)
            && $n->type() === NotificationType::DealCompleted
            && $n->message() === 'Сделка завершена, выплата отправлена'
            && $n->data() === ['order_id' => 'abc'],
    ));

    $deviceTokens = Mockery::mock(DeviceTokenRepositoryInterface::class);
    $deviceTokens->shouldReceive('findByUser')->once()->andReturn([]);

    $emailLookup = Mockery::mock(UserEmailLookupInterface::class);
    $emailLookup->shouldReceive('emailFor')->once()->andReturn(null);

    $dispatcher = new NotificationDispatcher($notifications, $deviceTokens, $emailLookup);
    $dispatcher->notify($userId, NotificationType::DealCompleted, 'Сделка завершена, выплата отправлена', ['order_id' => 'abc']);
});

it('dispatches a push job per device token', function (): void {
    Queue::fake();

    $userId = Id::generate();

    $notifications = Mockery::mock(NotificationRepositoryInterface::class);
    $notifications->shouldReceive('nextIdentity')->andReturn(Id::generate());
    $notifications->shouldReceive('save');

    $tokenA = DeviceToken::create(Id::generate(), $userId, DevicePlatform::Android, 'token-a');
    $tokenB = DeviceToken::create(Id::generate(), $userId, DevicePlatform::Ios, 'token-b');

    $deviceTokens = Mockery::mock(DeviceTokenRepositoryInterface::class);
    $deviceTokens->shouldReceive('findByUser')->once()->andReturn([$tokenA, $tokenB]);

    $emailLookup = Mockery::mock(UserEmailLookupInterface::class);
    $emailLookup->shouldReceive('emailFor')->once()->andReturn(null);

    $dispatcher = new NotificationDispatcher($notifications, $deviceTokens, $emailLookup);
    $dispatcher->notify($userId, NotificationType::NewMatch, 'У вас новый мэтч!');

    Queue::assertPushed(SendPushNotificationJob::class, 2);
    Queue::assertPushed(SendPushNotificationJob::class, fn ($job) => $job->token === 'token-a');
    Queue::assertPushed(SendPushNotificationJob::class, fn ($job) => $job->token === 'token-b');
});

it('does not dispatch push jobs without device tokens', function (): void {
    Queue::fake();

    $userId = Id::generate();

    $notifications = Mockery::mock(NotificationRepositoryInterface::class);
    $notifications->shouldReceive('nextIdentity')->andReturn(Id::generate());
    $notifications->shouldReceive('save');

    $deviceTokens = Mockery::mock(DeviceTokenRepositoryInterface::class);
    $deviceTokens->shouldReceive('findByUser')->once()->andReturn([]);

    $emailLookup = Mockery::mock(UserEmailLookupInterface::class);
    $emailLookup->shouldReceive('emailFor')->once()->andReturn(null);

    $dispatcher = new NotificationDispatcher($notifications, $deviceTokens, $emailLookup);
    $dispatcher->notify($userId, NotificationType::NewMatch, 'У вас новый мэтч!');

    Queue::assertNotPushed(SendPushNotificationJob::class);
});

it('queues an email when the user has an email address', function (): void {
    Mail::fake();

    $userId = Id::generate();

    $notifications = Mockery::mock(NotificationRepositoryInterface::class);
    $notifications->shouldReceive('nextIdentity')->andReturn(Id::generate());
    $notifications->shouldReceive('save');

    $deviceTokens = Mockery::mock(DeviceTokenRepositoryInterface::class);
    $deviceTokens->shouldReceive('findByUser')->once()->andReturn([]);

    $emailLookup = Mockery::mock(UserEmailLookupInterface::class);
    $emailLookup->shouldReceive('emailFor')->once()->andReturn('owner@example.com');

    $dispatcher = new NotificationDispatcher($notifications, $deviceTokens, $emailLookup);
    $dispatcher->notify($userId, NotificationType::NewMatch, 'У вас новый мэтч!');

    Mail::assertQueued(NotificationMail::class, fn (NotificationMail $mail) => $mail->body === 'У вас новый мэтч!');
});

it('does not queue an email without an address', function (): void {
    Mail::fake();

    $userId = Id::generate();

    $notifications = Mockery::mock(NotificationRepositoryInterface::class);
    $notifications->shouldReceive('nextIdentity')->andReturn(Id::generate());
    $notifications->shouldReceive('save');

    $deviceTokens = Mockery::mock(DeviceTokenRepositoryInterface::class);
    $deviceTokens->shouldReceive('findByUser')->once()->andReturn([]);

    $emailLookup = Mockery::mock(UserEmailLookupInterface::class);
    $emailLookup->shouldReceive('emailFor')->once()->andReturn(null);

    $dispatcher = new NotificationDispatcher($notifications, $deviceTokens, $emailLookup);
    $dispatcher->notify($userId, NotificationType::NewMatch, 'У вас новый мэтч!');

    Mail::assertNothingQueued();
});
