<?php

declare(strict_types=1);

use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Modules\Moderation\Application\Commands\BanUser\BanUserCommand;
use App\Modules\Moderation\Application\Commands\BanUser\BanUserHandler;
use App\Modules\Moderation\Domain\Exceptions\UserNotFoundException;
use App\Shared\Application\AuditLogWriterInterface;
use App\Shared\Domain\ValueObjects\Id;

it('blocks a user and revokes tokens', function (): void {
    $userId = Id::generate();
    $actorId = Id::generate();
    $user = User::register($userId, PhoneNumber::fromString('+79261234567'), 'hash', AccountType::Owner, true);

    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn($user);
    $users->shouldReceive('save')->once()->with(Mockery::on(fn (User $u) => $u->isBlocked()));
    $users->shouldReceive('revokeAllTokens')->once()->with(Mockery::on(fn (Id $id) => $id->equals($userId)));

    $auditLog = Mockery::mock(AuditLogWriterInterface::class);
    $auditLog->shouldReceive('record')->once()->with(
        Mockery::on(fn (Id $id) => $id->equals($actorId)),
        'user.banned',
        'user',
        $userId->toString(),
    );

    $handler = new BanUserHandler($users, $auditLog);
    $result = $handler->handle(new BanUserCommand($userId->toString(), $actorId->toString()));

    expect($result->isBlocked())->toBeTrue();
});

it('rejects banning a user that does not exist', function (): void {
    $users = Mockery::mock(UserRepositoryInterface::class);
    $users->shouldReceive('findById')->once()->andReturn(null);

    $auditLog = Mockery::mock(AuditLogWriterInterface::class);
    $auditLog->shouldNotReceive('record');

    $handler = new BanUserHandler($users, $auditLog);
    $handler->handle(new BanUserCommand(Id::generate()->toString(), Id::generate()->toString()));
})->throws(UserNotFoundException::class);
