<?php

namespace Database\Factories;

use App\Enums\HafalanKategoriJenis;
use App\Models\HafalanKategori;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HafalanKategori>
 */
class HafalanKategoriFactory extends Factory
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
            'jenis' => fake()->randomElement(HafalanKategoriJenis::cases()),
            'nama' => fake()->unique()->randomElement([
                'Juz Amma',
                'Jilid Yanbua',
                'Kitab Alfatih',
                'Imrithi',
                'Alfiyah',
            ]),
        ];
    }
}
