<?php

namespace Database\Factories;

use App\Enums\SantriGender;
use App\Enums\SantriStatus;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Santri>
 */
class SantriFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'nis' => fake()->unique()->numerify('#########'),
            'nisn' => fake()->unique()->numerify('##########'),
            'name' => fake()->name(),
            'gender' => fake()->randomElement(SantriGender::cases()),
            'birth_place' => fake()->city(),
            'birth_date' => fake()->dateTimeBetween('-18 years', '-6 years')->format('Y-m-d'),
            'address' => fake()->address(),
            'status' => SantriStatus::Aktif,
            'wali_name' => fake()->name(),
            'wali_phone' => fake()->phoneNumber(),
            'entry_date' => fake()->dateTimeBetween('-4 years', 'now')->format('Y-m-d'),
        ];
    }

    /**
     * Indicate that the santri has an account linked wali (guardian).
     */
    public function withWaliAccount(): static
    {
        return $this->state(fn (array $attributes) => [
            'wali_user_id' => User::factory(),
        ]);
    }

    /**
     * Indicate that the santri is an alumnus.
     */
    public function alumni(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SantriStatus::Alumni,
            'exit_date' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
        ]);
    }
}
