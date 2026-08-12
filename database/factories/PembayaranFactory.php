<?php

namespace Database\Factories;

use App\Enums\PembayaranJenis;
use App\Enums\PembayaranMetode;
use App\Enums\PembayaranStatus;
use App\Models\Pembayaran;
use App\Models\Santri;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pembayaran>
 */
class PembayaranFactory extends Factory
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
            'jenis' => fake()->randomElement(PembayaranJenis::cases()),
            'jumlah' => fake()->randomFloat(2, 50000, 3000000),
            'tanggal' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'metode' => fake()->randomElement(PembayaranMetode::cases()),
            'status' => PembayaranStatus::Lunas,
            'keterangan' => fake()->optional()->sentence(),
            'recorded_by' => null,
        ];
    }

    /**
     * Indicate that the payment is still pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PembayaranStatus::Pending,
        ]);
    }
}
