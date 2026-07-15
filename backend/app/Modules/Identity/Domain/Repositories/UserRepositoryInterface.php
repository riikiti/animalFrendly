<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Repositories;

use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use App\Shared\Domain\ValueObjects\Id;

interface UserRepositoryInterface
{
    public function nextIdentity(): Id;

    public function existsByPhone(PhoneNumber $phone): bool;

    public function save(User $user): void;

    public function findById(Id $id): ?User;

    public function findByPhone(PhoneNumber $phone): ?User;

    /**
     * Отзывает все выданные Sanctum-токены пользователя — используется при бане, чтобы
     * доступ прекращался немедленно, а не только блокировал последующие входы.
     */
    public function revokeAllTokens(Id $userId): void;
}
