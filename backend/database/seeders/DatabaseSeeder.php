<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Catalog\CatalogSeeder;
use Database\Seeders\Demo\DemoSeeder;
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
        $this->call(ShopCategorySeeder::class);

        // Демо-контент (реальные фото + реалистичные описания) — только для локального
        // ручного браузинга, см. Database\Seeders\Demo\DemoSeeder.
        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
