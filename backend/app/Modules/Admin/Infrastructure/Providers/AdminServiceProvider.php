<?php

declare(strict_types=1);

namespace App\Modules\Admin\Infrastructure\Providers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User as IdentityUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('access-admin-panel', static fn (IdentityUser $user): bool => in_array(
            $user->account_type,
            ['admin', 'moderator'],
            true,
        ));
    }
}
