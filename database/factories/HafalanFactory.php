<?php

namespace Database\Factories;

use App\Enums\HafalanType;
use App\Models\Hafalan;
use App\Models\Santri;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hafalan>
 */
class HafalanFactory extends Factory
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
            'santri_id' => Santri::factory(),
            'ustad_user_id' => User::factory(),
            'type' => fake()->randomElement(HafalanType::cases()),
            'juz' => fake()->numberBetween(1, 30),
            'surat' => 'An-Naba',
            'ayat_from' => fake()->numberBetween(1, 10),
            'ayat_to' => fake()->numberBetween(11, 40),
            'tanggal' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the setoran is a new hafalan.
     */
    public function hafalanBaru(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => HafalanType::HafalanBaru,
        ]);
    }

    /**
     * Indicate that the setoran is a murajaah (review).
     */
    public function murajaah(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => HafalanType::Murajaah,
        ]);
    }
}
