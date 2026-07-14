<?php

declare(strict_types=1);

namespace Database\Seeders\Catalog;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Breed;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;
use Illuminate\Database\Seeder;

final class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'dog' => [
                'name' => 'Собака',
                'breeds' => ['Лабрадор', 'Немецкая овчарка', 'Хаски', 'Метис'],
            ],
            'cat' => [
                'name' => 'Кошка',
                'breeds' => ['Британская', 'Мейн-кун', 'Сиамская', 'Беспородная'],
            ],
            'bird' => [
                'name' => 'Птица',
                'breeds' => ['Волнистый попугай', 'Корелла'],
            ],
            'other' => [
                'name' => 'Другое',
                'breeds' => [],
            ],
        ];

        foreach ($catalog as $slug => $definition) {
            $species = Species::query()->firstOrCreate(
                ['slug' => $slug],
                ['name_ru' => $definition['name'], 'is_active' => true],
            );

            foreach ($definition['breeds'] as $breedName) {
                Breed::query()->firstOrCreate(
                    ['species_id' => $species->id, 'slug' => str($breedName)->slug()->toString()],
                    ['name_ru' => $breedName, 'is_active' => true],
                );
            }
        }
    }
}
