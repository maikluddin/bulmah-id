<?php

namespace Database\Factories;

use App\Enums\PerizinanJenis;
use App\Enums\PerizinanStatus;
use App\Models\Perizinan;
use App\Models\Santri;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Perizinan>
 */
class PerizinanFactory extends Factory
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
            'jenis' => fake()->randomElement(PerizinanJenis::cases()),
            'alasan' => fake()->sentence(),
            'tanggal_mulai' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'tanggal_selesai' => fake()->optional()->dateTimeBetween('now', '+1 month')?->format('Y-m-d'),
            'status' => PerizinanStatus::Pending,
            'catatan' => fake()->optional()->sentence(),
            'decided_by' => null,
        ];
    }

    /**
     * Indicate that the permit was approved.
     */
    public function disetujui(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PerizinanStatus::Disetujui,
        ]);
    }

    /**
     * Indicate that the permit was rejected.
     */
    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PerizinanStatus::Ditolak,
        ]);
    }
}
