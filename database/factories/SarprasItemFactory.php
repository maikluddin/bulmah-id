<?php

namespace Database\Factories;

use App\Enums\SarprasKategori;
use App\Enums\SarprasKondisi;
use App\Models\SarprasItem;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SarprasItem>
 */
class SarprasItemFactory extends Factory
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
            'nama' => fake()->unique()->randomElement([
                'Meja dan Kursi',
                'Papan Tulis',
                'Proyektor',
                'Lemari Buku',
                'Tempat Tidur',
                'Komputer',
                'Meja Guru',
                'Peralatan Olahraga',
            ]),
            'kategori' => fake()->randomElement(SarprasKategori::cases()),
            'lokasi' => fake()->randomElement(['Ruang 1', 'Ruang 2', 'Aula', 'Asrama A', 'Asrama B', 'Perpustakaan']),
            'jumlah' => fake()->numberBetween(1, 50),
            'kondisi' => fake()->randomElement(SarprasKondisi::cases()),
            'keterangan' => fake()->optional()->sentence(),
            'foto_path' => null,
        ];
    }
}
