<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Commands\UnbanUser;

use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Moderation\Domain\Exceptions\UserNotFoundException;
use App\Shared\Application\AuditLogWriterInterface;
use App\Shared\Domain\ValueObjects\Id;

final class UnbanUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AuditLogWriterInterface $auditLog,
    ) {}

    public function handle(UnbanUserCommand $command): User
    {
        $userId = Id::fromString($command->userId);
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw UserNotFoundException::forId($command->userId);
        }

        $user->unblock();
        $this->users->save($user);

        $this->auditLog->record(Id::fromString($command->actingUserId), 'user.unbanned', 'user', $command->userId);

        return $user;
    }
}
