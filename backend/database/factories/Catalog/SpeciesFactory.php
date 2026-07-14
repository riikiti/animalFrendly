<?php

declare(strict_types=1);

namespace Database\Factories\Catalog;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Species>
 */
final class SpeciesFactory extends Factory
{
    protected $model = Species::class;

    public function definition(): array
    {
        $slug = fake()->unique()->word();

        return [
            'slug' => $slug,
            'name_ru' => ucfirst($slug),
            'is_active' => true,
        ];
    }
}
