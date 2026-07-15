<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Application\Commands\BanUser;

use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Moderation\Domain\Exceptions\UserNotFoundException;
use App\Shared\Application\AuditLogWriterInterface;
use App\Shared\Domain\ValueObjects\Id;

/**
 * Прямая зависимость от Domain-репозитория Identity (не через Application-контракт) — тот же
 * приём, что уже используется в Chat (MatchParticipantGuard/AdoptionRequestParticipantGuard),
 * см. docs/plan/03-architecture.md.
 */
final class BanUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AuditLogWriterInterface $auditLog,
    ) {}

    public function handle(BanUserCommand $command): User
    {
        $userId = Id::fromString($command->userId);
        $user = $this->users->findById($userId);

        if ($user === null) {
            throw UserNotFoundException::forId($command->userId);
        }

        $user->block();
        $this->users->save($user);
        $this->users->revokeAllTokens($userId);

        $this->auditLog->record(Id::fromString($command->actingUserId), 'user.banned', 'user', $command->userId);

        return $user;
    }
}
