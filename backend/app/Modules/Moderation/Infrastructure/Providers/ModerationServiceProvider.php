<?php

declare(strict_types=1);

namespace App\Modules\Moderation\Infrastructure\Providers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use App\Modules\Moderation\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Moderation\Domain\Repositories\ReportRepositoryInterface;
use App\Modules\Moderation\Domain\Repositories\ReviewRepositoryInterface;
use App\Modules\Moderation\Infrastructure\Adapters\AuditLogWriter;
use App\Modules\Moderation\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditLogRepository;
use App\Modules\Moderation\Infrastructure\Persistence\Eloquent\Repositories\EloquentReportRepository;
use App\Modules\Moderation\Infrastructure\Persistence\Eloquent\Repositories\EloquentReviewRepository;
use App\Shared\Application\AuditLogWriterInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class ModerationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ReportRepositoryInterface::class, EloquentReportRepository::class);
        $this->app->bind(ReviewRepositoryInterface::class, EloquentReviewRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, EloquentAuditLogRepository::class);

        // Интерфейс объявлен в Shared (fan-in — многие модули потенциально пишут в журнал),
        // но байндинг делает модуль-поставщик реализации, см. app/Shared/Application/AuditLogWriterInterface.php.
        $this->app->bind(AuditLogWriterInterface::class, AuditLogWriter::class);
    }

    public function boot(): void
    {
        // RBAC через Gate, не через if ($user->accountType === ...) в контроллере — тот же
        // принцип, что resolve-marketplace-disputes, см. docs/rules/01-backend.md.
        Gate::define('moderate-reports', static fn (IdentityUser $user): bool => in_array(
            $user->account_type,
            ['admin', 'moderator'],
            true,
        ));

        Gate::define('ban-users', static fn (IdentityUser $user): bool => in_array(
            $user->account_type,
            ['admin', 'moderator'],
            true,
        ));
    }
}
