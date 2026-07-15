<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Console;

use App\Modules\Identity\Domain\Entities\User;
use App\Modules\Identity\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObjects\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * Публичная регистрация намеренно не даёт выбрать admin/moderator
 * (см. Presentation/Http/Requests/RegisterRequest.php) — единственный способ завести
 * staff-аккаунт (в т.ч. самый первый администратор при разворачивании).
 */
final class CreateStaffUserCommand extends Command
{
    protected $signature = 'identity:create-staff {phone} {password} {--role=moderator : admin или moderator}';

    protected $description = 'Создаёт staff-аккаунт (admin/moderator), недоступный через публичную регистрацию';

    public function handle(UserRepositoryInterface $users, Hasher $hasher): int
    {
        $role = (string) $this->option('role');

        if (! in_array($role, ['admin', 'moderator'], true)) {
            $this->error('--role должен быть admin или moderator.');

            return self::FAILURE;
        }

        $phone = PhoneNumber::fromString((string) $this->argument('phone'));

        if ($users->existsByPhone($phone)) {
            $this->error('Пользователь с таким телефоном уже существует.');

            return self::FAILURE;
        }

        $user = User::register(
            id: $users->nextIdentity(),
            phone: $phone,
            passwordHash: $hasher->make((string) $this->argument('password')),
            accountType: AccountType::from($role),
            personalDataConsentGiven: true,
        );
        $users->save($user);

        $this->info("Staff-аккаунт создан: {$phone->value()} ({$role}).");

        return self::SUCCESS;
    }
}
