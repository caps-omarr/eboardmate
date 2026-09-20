<?php

namespace Tests\Feature\Security;

use App\Models\BoardingHouse;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AntiHoardingAndConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Prevent actual mail transport during tests
        Mail::fake();
    }

    /**
     * Test 1: Verify duplicate pending reservation attempts with the same email are rejected.
     */
    public function test_duplicate_pending_reservation_with_same_email_is_rejected(): void
    {
        $owner = User::factory()->owner()->create();
        $boardingHouse = BoardingHouse::factory()->create([
            'owner_id' => $owner->id,
            'available_rooms' => 2,
            'available_bedspaces' => 4,
        ]);

        $guestEmail = 'student.applicant@gmail.com';
        $guestPhone = '09171234567';

        // 1. Submit initial reservation
        $firstResponse = $this->post(route('boarding-houses.reservations.store', $boardingHouse->slug), [
            'full_name' => 'Juan Dela Cruz',
            'email' => $guestEmail,
            'phone' => $guestPhone,
            'preferred_move_in_date' => now()->addDays(5)->format('Y-m-d'),
            'message' => 'First reservation inquiry',
            'accepted_terms' => '1',
        ]);

        $firstResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservations', [
            'boarding_house_id' => $boardingHouse->id,
            'guest_email' => $guestEmail,
            'status' => Reservation::STATUS_PENDING,
        ]);

        // 2. Submit second reservation with the exact same email (hoarding attempt)
        $secondResponse = $this->post(route('boarding-houses.reservations.store', $boardingHouse->slug), [
            'full_name' => 'Juan Dela Cruz Alternate',
            'email' => $guestEmail,
            'phone' => '09998887777',
            'preferred_move_in_date' => now()->addDays(6)->format('Y-m-d'),
            'message' => 'Second reservation inquiry attempting to hoard bedspace',
            'accepted_terms' => '1',
        ]);

        $secondResponse->assertSessionHasErrors('reservation');

        // Verify only 1 reservation exists in the database
        $this->assertEquals(1, Reservation::where('guest_email', $guestEmail)->count());
    }

    /**
     * Test 1b: Verify duplicate reservation attempts with the same phone number are rejected.
     */
    public function test_duplicate_pending_reservation_with_same_phone_is_rejected(): void
    {
        $owner = User::factory()->owner()->create();
        $boardingHouse = BoardingHouse::factory()->create([
            'owner_id' => $owner->id,
            'available_rooms' => 2,
            'available_bedspaces' => 4,
        ]);

        $sharedPhone = '09281234567';

        // 1. Create an existing pending reservation with this phone
        Reservation::factory()->create([
            'boarding_house_id' => $boardingHouse->id,
            'guest_email' => 'user1@gmail.com',
            'guest_phone' => $sharedPhone,
            'status' => Reservation::STATUS_PENDING,
            'expires_at' => now()->addHours(24),
        ]);

        // 2. Attempt to create another reservation with a different email but the same phone number
        $response = $this->post(route('boarding-houses.reservations.store', $boardingHouse->slug), [
            'full_name' => 'Maria Santos',
            'email' => 'user2@gmail.com',
            'phone' => $sharedPhone,
            'preferred_move_in_date' => now()->addDays(4)->format('Y-m-d'),
            'message' => 'Attempting duplicate booking via phone spoofing',
            'accepted_terms' => '1',
        ]);

        $response->assertSessionHasErrors('reservation');
        $this->assertEquals(1, Reservation::where('guest_phone', $sharedPhone)->count());
    }

    /**
     * Test 1c: Verify guest CAN apply again if previous reservation was rejected or expired.
     */
    public function test_guest_can_apply_again_if_previous_reservation_was_rejected_or_expired(): void
    {
        $owner = User::factory()->owner()->create();
        $boardingHouse = BoardingHouse::factory()->create([
            'owner_id' => $owner->id,
            'available_rooms' => 2,
            'available_bedspaces' => 4,
        ]);

        $guestEmail = 'past.guest@gmail.com';
        $guestPhone = '09331112233';

        // Past rejected reservation
        Reservation::factory()->create([
            'boarding_house_id' => $boardingHouse->id,
            'guest_email' => $guestEmail,
            'guest_phone' => $guestPhone,
            'status' => Reservation::STATUS_REJECTED,
            'rejected_at' => now()->subDays(2),
        ]);

        // New submission should be allowed
        $response = $this->post(route('boarding-houses.reservations.store', $boardingHouse->slug), [
            'full_name' => 'Past Guest',
            'email' => $guestEmail,
            'phone' => $guestPhone,
            'preferred_move_in_date' => now()->addDays(3)->format('Y-m-d'),
            'message' => 'Re-applying after previous rejection',
            'accepted_terms' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(1, Reservation::where('guest_email', $guestEmail)->where('status', Reservation::STATUS_PENDING)->count());
    }

    /**
     * Test 2: Verify atomic reservation requests fail gracefully when available capacity reaches zero.
     */
    public function test_atomic_reservation_fails_when_boarding_house_is_full(): void
    {
        $owner = User::factory()->owner()->create();
        // Fully booked boarding house (0 rooms, 0 bedspaces)
        $fullHouse = BoardingHouse::factory()->full()->create([
            'owner_id' => $owner->id,
        ]);

        $this->assertTrue($fullHouse->isFull());

        // HTTP attempt
        $response = $this->post(route('boarding-houses.reservations.store', $fullHouse->slug), [
            'full_name' => 'Ana Reyes',
            'email' => 'ana.reyes@gmail.com',
            'phone' => '09459876543',
            'preferred_move_in_date' => now()->addDays(5)->format('Y-m-d'),
            'message' => 'Inquiring for full house',
            'accepted_terms' => '1',
        ]);

        $response->assertSessionHasErrors('reservation');

        // Direct Service invocation throws ValidationException
        $service = app(ReservationService::class);

        $this->expectException(ValidationException::class);
        $service->createReservation($fullHouse, [
            'full_name' => 'Direct Invocation Guest',
            'email' => 'direct.guest@gmail.com',
            'phone' => '09112223344',
            'preferred_move_in_date' => now()->addDays(5)->format('Y-m-d'),
        ]);
    }
}
