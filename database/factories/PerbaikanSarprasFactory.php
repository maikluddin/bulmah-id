<?php

namespace Database\Factories;

use App\Enums\PerbaikanStatus;
use App\Models\PerbaikanSarpras;
use App\Models\SarprasItem;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PerbaikanSarpras>
 */
class PerbaikanSarprasFactory extends Factory
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
            'sarpras_item_id' => SarprasItem::factory(),
            'pelapor_user_id' => User::factory(),
            'deskripsi' => fake()->sentence(),
            'status' => fake()->randomElement(PerbaikanStatus::cases()),
            'catatan' => fake()->optional()->sentence(),
        ];
    }
}
