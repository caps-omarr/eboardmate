<?php

namespace Database\Factories;

use App\Models\BoardingHouse;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'boarding_house_id' => BoardingHouse::factory(),
            'reference_code' => 'RSV-' . strtoupper(Str::random(8)),
            'guest_name' => fake()->name(),
            'guest_email' => fake()->safeEmail(),
            'guest_phone' => '09' . fake()->numerify('#########'),
            'preferred_move_in_date' => now()->addDays(3)->format('Y-m-d'),
            'message' => fake()->sentence(),
            'status' => Reservation::STATUS_PENDING,
            'expires_at' => now()->addHours(24),
            'submission_ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit Feature Test',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Reservation::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Reservation::STATUS_REJECTED,
            'rejected_at' => now(),
            'owner_response' => 'No slots available.',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Reservation::STATUS_EXPIRED,
            'expired_at' => now()->subHour(),
            'expires_at' => now()->subHour(),
        ]);
    }
}
