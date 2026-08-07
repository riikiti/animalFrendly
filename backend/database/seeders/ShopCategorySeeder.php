<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Shop\Infrastructure\Persistence\Eloquent\Models\ShopCategory;
use Illuminate\Database\Seeder;

final class ShopCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'food', 'name' => 'Корма и лакомства', 'position' => 10],
            ['slug' => 'toys', 'name' => 'Игрушки', 'position' => 20],
            ['slug' => 'gear', 'name' => 'Амуниция', 'position' => 30],
            ['slug' => 'care', 'name' => 'Уход и гигиена', 'position' => 40],
            ['slug' => 'home', 'name' => 'Лежанки и домики', 'position' => 50],
            ['slug' => 'health', 'name' => 'Здоровье', 'position' => 60],
        ];

        foreach ($categories as $category) {
            ShopCategory::query()->updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
