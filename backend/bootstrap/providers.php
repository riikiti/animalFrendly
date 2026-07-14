<?php

use App\Modules\Identity\Infrastructure\Providers\IdentityServiceProvider;
use App\Providers\AppServiceProvider;
use App\Shared\Infrastructure\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    IdentityServiceProvider::class,
];
