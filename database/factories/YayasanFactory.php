<?php

namespace Database\Factories;

use App\Models\Yayasan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Yayasan>
 */
class YayasanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'description' => fake()->paragraph(),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'website' => fake()->url(),
        ];
    }
}
