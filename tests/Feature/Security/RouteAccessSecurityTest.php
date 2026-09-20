<?php

namespace Tests\Feature\Security;

use App\Models\BoardingHouse;
use App\Models\BoardingHousePhoto;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteAccessSecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Verify unauthenticated guests are redirected to login when accessing admin or owner routes.
     */
    public function test_unauthenticated_guests_are_redirected_when_accessing_admin_and_owner_routes(): void
    {
        // Admin endpoints redirect guests to admin login
        $responseAdmin = $this->get('/admin/dashboard');
        $responseAdmin->assertRedirect('/admin/login');

        $responseAdminOwners = $this->get('/admin/owners');
        $responseAdminOwners->assertRedirect('/admin/login');

        $responseAdminReports = $this->get('/admin/reports');
        $responseAdminReports->assertRedirect('/admin/login');

        // Owner endpoints redirect guests to owner login
        $responseOwner = $this->get('/owner/dashboard');
        $responseOwner->assertRedirect('/owner/login');

        $responseOwnerReservations = $this->get('/owner/reservations');
        $responseOwnerReservations->assertRedirect('/owner/login');

        $responseOwnerListing = $this->get('/owner/listing');
        $responseOwnerListing->assertRedirect('/owner/login');
    }

    /**
     * Test 2: Verify that a non-admin user (e.g. owner) cannot access administrative endpoints (must return 403 Forbidden).
     */
    public function test_owner_user_cannot_access_administrative_endpoints(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner);

        // Accessing admin routes must abort with 403 Forbidden
        $this->get('/admin/dashboard')->assertStatus(403);
        $this->get('/admin/owners')->assertStatus(403);
        $this->get('/admin/reports')->assertStatus(403);
        $this->get('/admin/boarding-houses')->assertStatus(403);
        $this->get('/admin/activity-logs')->assertStatus(403);
        $this->post('/admin/owners', ['name' => 'Hacker'])->assertStatus(403);
    }

    /**
     * Test 3: Verify that an owner cannot access or mutate another owner's listings or reservations (IDOR protection).
     */
    public function test_owner_cannot_mutate_another_owners_reservations_or_photos(): void
    {
        $ownerA = User::factory()->owner()->create();
        $houseA = BoardingHouse::factory()->create(['owner_id' => $ownerA->id]);

        $ownerB = User::factory()->owner()->create();
        $houseB = BoardingHouse::factory()->create(['owner_id' => $ownerB->id]);

        $reservationB = Reservation::factory()->create([
            'boarding_house_id' => $houseB->id,
            'status' => Reservation::STATUS_PENDING,
        ]);

        $photoB = BoardingHousePhoto::create([
            'boarding_house_id' => $houseB->id,
            'file_path' => 'boarding-houses/' . $houseB->id . '/photos/sample.jpg',
            'original_name' => 'sample.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024,
            'alt_text' => 'Sample Photo',
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        // Authenticate as Owner A
        $this->actingAs($ownerA);

        // Attempt IDOR on Owner B's reservation: Approve
        $approveResponse = $this->post(route('owner.reservations.approve', $reservationB->id), [
            'owner_response' => 'Malicious approval attempt',
        ]);
        $approveResponse->assertStatus(403);

        // Attempt IDOR on Owner B's reservation: Reject
        $rejectResponse = $this->post(route('owner.reservations.reject', $reservationB->id), [
            'owner_response' => 'Malicious rejection attempt',
        ]);
        $rejectResponse->assertStatus(403);

        // Attempt IDOR on Owner B's reservation: Archive
        $archiveResponse = $this->post(route('owner.reservations.archive', $reservationB->id));
        $archiveResponse->assertStatus(403);

        // Attempt IDOR on Owner B's photos: Set Primary
        $primaryPhotoResponse = $this->post(route('owner.listing.photos.primary', $photoB->id));
        $primaryPhotoResponse->assertStatus(403);

        // Attempt IDOR on Owner B's photos: Delete
        $deletePhotoResponse = $this->delete(route('owner.listing.photos.destroy', $photoB->id));
        $deletePhotoResponse->assertStatus(403);

        // Assert Owner B's reservation remained untouched
        $this->assertEquals(Reservation::STATUS_PENDING, $reservationB->fresh()->status);
        $this->assertNull($reservationB->fresh()->approved_at);
        $this->assertNull($reservationB->fresh()->rejected_at);
        $this->assertFalse($photoB->fresh()->is_primary);
    }

    /**
     * Test 4: Verify that public tracking endpoints correctly reject invalid, mismatched, or spoofed reference codes.
     */
    public function test_public_tracking_endpoint_rejects_invalid_or_mismatched_reference_codes(): void
    {
        $owner = User::factory()->owner()->create();
        $house = BoardingHouse::factory()->create(['owner_id' => $owner->id]);

        $reservation = Reservation::factory()->create([
            'boarding_house_id' => $house->id,
            'reference_code' => 'EBM-2026-000123',
            'guest_email' => 'legitimate.guest@example.com',
            'status' => Reservation::STATUS_PENDING,
        ]);

        // 1. Non-existent reference code
        $fakeCodeResponse = $this->post(route('track-reservation.search'), [
            'reference_code' => 'EBM-2026-999999',
            'email' => 'legitimate.guest@example.com',
        ]);
        $fakeCodeResponse->assertSessionHasErrors('tracking');

        // 2. Mismatched email for existing reference code (spoofing attempt)
        $spoofedEmailResponse = $this->post(route('track-reservation.search'), [
            'reference_code' => 'EBM-2026-000123',
            'email' => 'attacker@example.com',
        ]);
        $spoofedEmailResponse->assertSessionHasErrors('tracking');

        // 3. Valid code with correct matching email succeeds
        $validResponse = $this->post(route('track-reservation.search'), [
            'reference_code' => 'EBM-2026-000123',
            'email' => 'legitimate.guest@example.com',
        ]);
        $validResponse->assertSessionHasNoErrors();
        $validResponse->assertRedirect(route('track-reservation'));
        $validResponse->assertSessionHas('tracking_result');
    }
}
