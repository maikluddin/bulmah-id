<?php

namespace Database\Factories;

use App\Enums\WalletTransactionType;
use App\Models\Santri;
use App\Models\Team;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
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
            'type' => fake()->randomElement(WalletTransactionType::cases()),
            'amount' => fake()->randomFloat(2, 1000, 200000),
            'tanggal' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'description' => fake()->optional()->sentence(),
            'recorded_by' => null,
        ];
    }

    /**
     * Indicate that the transaction is a top-up.
     */
    public function setor(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WalletTransactionType::Setor,
        ]);
    }

    /**
     * Indicate that the transaction is a withdrawal.
     */
    public function penarikan(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => WalletTransactionType::Penarikan,
        ]);
    }
}
