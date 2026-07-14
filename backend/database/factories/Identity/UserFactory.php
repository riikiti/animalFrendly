<?php

declare(strict_types=1);

namespace Database\Factories\Identity;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'phone' => '+7'.fake()->unique()->numerify('9#########'),
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => Hash::make('password'),
            'account_type' => 'owner',
            'status' => 'active',
            'personal_data_consent_at' => now(),
        ];
    }
}
