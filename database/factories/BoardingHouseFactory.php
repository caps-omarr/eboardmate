<?php

namespace Database\Factories;

use App\Models\BoardingHouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BoardingHouse>
 */
class BoardingHouseFactory extends Factory
{
    protected $model = BoardingHouse::class;

    public function definition(): array
    {
        $name = fake()->company() . ' Boarding House';

        return [
            'owner_id' => User::factory()->owner(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(100, 9999),
            'description' => fake()->paragraph(),
            'location_description' => fake()->sentence(),
            'address' => fake()->address(),
            'latitude' => 10.13485,
            'longitude' => 124.32274,
            'rent_price' => 1500.00,
            'total_rooms' => 5,
            'available_rooms' => 3,
            'total_bedspaces' => 10,
            'available_bedspaces' => 6,
            'amenities' => ['WiFi', 'Kitchen Access'],
            'rules' => 'No loud noise after 10 PM.',
            'allowed_genders' => 'Any Gender (All)',
            'includes_water' => true,
            'includes_electricity' => true,
            'status' => BoardingHouse::STATUS_APPROVED,
            'is_verified' => true,
            'verified_at' => now(),
        ];
    }

    public function full(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_rooms' => 0,
            'available_bedspaces' => 0,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BoardingHouse::STATUS_PENDING,
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }
}
