<?php

namespace Database\Factories;

use App\Models\Santri;
use App\Models\SantriWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SantriWallet>
 */
class SantriWalletFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'santri_id' => Santri::factory(),
            'balance' => fake()->randomFloat(2, 0, 500000),
        ];
    }
}
