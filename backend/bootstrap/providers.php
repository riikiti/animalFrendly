<?php

use App\Modules\Catalog\Infrastructure\Providers\CatalogServiceProvider;
use App\Modules\Chat\Infrastructure\Providers\ChatServiceProvider;
use App\Modules\Identity\Infrastructure\Providers\IdentityServiceProvider;
use App\Modules\Marketplace\Infrastructure\Providers\MarketplaceServiceProvider;
use App\Modules\Matching\Infrastructure\Providers\MatchingServiceProvider;
use App\Modules\Payment\Infrastructure\Providers\PaymentServiceProvider;
use App\Modules\Profile\Infrastructure\Providers\ProfileServiceProvider;
use App\Modules\Shelter\Infrastructure\Providers\ShelterServiceProvider;
use App\Providers\AppServiceProvider;
use App\Shared\Infrastructure\Providers\SharedServiceProvider;

return [
    AppServiceProvider::class,
    SharedServiceProvider::class,
    IdentityServiceProvider::class,
    CatalogServiceProvider::class,
    ProfileServiceProvider::class,
    MatchingServiceProvider::class,
    ChatServiceProvider::class,
    ShelterServiceProvider::class,
    MarketplaceServiceProvider::class,
    PaymentServiceProvider::class,
];
