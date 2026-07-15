<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Catalog\CatalogSeeder;
use Database\Seeders\Subscription\SubscriptionPlanSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(CatalogSeeder::class);
        $this->call(SubscriptionPlanSeeder::class);
    }
}
