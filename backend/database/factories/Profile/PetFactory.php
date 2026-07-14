<?php

declare(strict_types=1);

namespace Database\Factories\Profile;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Species;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use App\Modules\Profile\Infrastructure\Persistence\Eloquent\Models\Pet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pet>
 */
final class PetFactory extends Factory
{
    protected $model = Pet::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'owner_id' => User::factory(),
            'species_id' => Species::factory(),
            'breed_id' => null,
            'name' => fake()->firstName(),
            'sex' => fake()->randomElement(['male', 'female']),
            'purpose' => 'social',
            'is_vaccinated' => false,
            'status' => 'active',
        ];
    }
}
