<?php

declare(strict_types=1);

use App\Modules\Notification\Application\Contracts\UserEmailLookupInterface;
use App\Modules\Notification\Application\Services\NotificationDispatcher;
use App\Modules\Notification\Domain\Entities\Notification;
use App\Modules\Notification\Domain\Repositories\DeviceTokenRepositoryInterface;
use App\Modules\Notification\Domain\Repositories\NotificationRepositoryInterface;
use App\Modules\Notification\Infrastructure\Listeners\NotifyOnAdoptionRequestApproved;
use App\Modules\Shelter\Domain\Entities\AdoptionRequest;
use App\Modules\Shelter\Domain\Events\AdoptionRequestApproved;
use App\Modules\Shelter\Domain\Repositories\AdoptionRequestRepositoryInterface;
use App\Shared\Domain\ValueObjects\Id;

it('notifies the requester when the adoption request is approved', function (): void {
    $requesterId = Id::generate();
    $requestId = Id::generate();
    $request = AdoptionRequest::create($requestId, Id::generate(), $requesterId, null);

    $requests = Mockery::mock(AdoptionRequestRepositoryInterface::class);
    $requests->shouldReceive('findById')->with($requestId)->once()->andReturn($request);

    $notifications = Mockery::mock(NotificationRepositoryInterface::class);
    $notifications->shouldReceive('nextIdentity')->once()->andReturn(Id::generate());
    $notifications->shouldReceive('save')->once()->with(Mockery::on(
        fn (Notification $n) => $n->userId()->equals($requesterId),
    ));

    $deviceTokens = Mockery::mock(DeviceTokenRepositoryInterface::class);
    $deviceTokens->shouldReceive('findByUser')->once()->andReturn([]);

    $emailLookup = Mockery::mock(UserEmailLookupInterface::class);
    $emailLookup->shouldReceive('emailFor')->once()->andReturn(null);

    $listener = new NotifyOnAdoptionRequestApproved($requests, new NotificationDispatcher($notifications, $deviceTokens, $emailLookup));
    $listener->handle(new AdoptionRequestApproved($requestId, new DateTimeImmutable));
});
