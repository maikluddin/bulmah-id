<?php

namespace Database\Factories;

use App\Enums\AbsensiStatus;
use App\Models\Absensi;
use App\Models\Santri;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Absensi>
 */
class AbsensiFactory extends Factory
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
            'tanggal' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'status' => fake()->randomElement(AbsensiStatus::cases()),
            'keterangan' => fake()->optional()->sentence(),
            'recorded_by' => null,
        ];
    }

    /**
     * Indicate that the santri was present.
     */
    public function hadir(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AbsensiStatus::Hadir,
        ]);
    }

    /**
     * Indicate that the santri was absent.
     */
    public function alpa(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AbsensiStatus::Alpa,
        ]);
    }
}
