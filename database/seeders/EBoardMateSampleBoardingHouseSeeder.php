<?php

namespace Database\Seeders;

use App\Models\BoardingHouse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EBoardMateSampleBoardingHouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('email', 'admin@eboardmate.test')->first();

        $ownerOne = User::updateOrCreate(
            ['email' => 'owner@eboardmate.test'],
            [
                'name' => 'Sample Boarding House Owner',
                'phone' => '09987654321',
                'password' => Hash::make('EBoardMate2026!'),
                'role' => User::ROLE_OWNER,
                'status' => User::STATUS_ACTIVE,
            ]
        );

        $ownerTwo = User::updateOrCreate(
            ['email' => 'owner2@eboardmate.test'],
            [
                'name' => 'Second Sample Owner',
                'phone' => '09987654322',
                'password' => Hash::make('EBoardMate2026!'),
                'role' => User::ROLE_OWNER,
                'status' => User::STATUS_ACTIVE,
            ]
        );

        $ownerThree = User::updateOrCreate(
            ['email' => 'owner3@eboardmate.test'],
            [
                'name' => 'Third Sample Owner',
                'phone' => '09987654323',
                'password' => Hash::make('EBoardMate2026!'),
                'role' => User::ROLE_OWNER,
                'status' => User::STATUS_ACTIVE,
            ]
        );

        $sampleBoardingHouses = [
            [
                'owner_id' => $ownerOne->id,
                'name' => 'Sample Green Stay Boarding House',
                'slug' => 'sample-green-stay-boarding-house',
                'description' => 'A sample verified boarding house near Talibon Polytechnic College for map testing.',
                'location_description' => 'Located near the school area. This is sample data for development testing only.',
                'address' => 'Sample Address 1, Talibon, Bohol',
                'latitude' => 10.13777,
                'longitude' => 124.32208,
                'rent_price' => 2500.00,
                'total_rooms' => 6,
                'available_rooms' => 3,
                'total_bedspaces' => 12,
                'available_bedspaces' => 7,
                'amenities' => ['WiFi', 'Water', 'Electricity', 'Study Area'],
                'rules' => 'Keep the area clean. No loud noise during study hours.',
            ],
            [
                'owner_id' => $ownerTwo->id,
                'name' => 'Sample TPC Corner Boarding House',
                'slug' => 'sample-tpc-corner-boarding-house',
                'description' => 'A second sample approved boarding house for testing multiple map markers.',
                'location_description' => 'A short walking distance from the campus. This is sample data only.',
                'address' => 'Sample Address 2, Talibon, Bohol',
                'latitude' => 10.13603,
                'longitude' => 124.32436,
                'rent_price' => 2200.00,
                'total_rooms' => 5,
                'available_rooms' => 2,
                'total_bedspaces' => 10,
                'available_bedspaces' => 4,
                'amenities' => ['Water', 'Electricity', 'Shared Kitchen'],
                'rules' => 'Visitors are allowed only during permitted hours.',
            ],
            [
                'owner_id' => $ownerThree->id,
                'name' => 'Sample Student Haven Boarding House',
                'slug' => 'sample-student-haven-boarding-house',
                'description' => 'A third sample verified listing used to test the public Mapbox markers.',
                'location_description' => 'Near the main road going to Talibon Polytechnic College. This is sample data only.',
                'address' => 'Sample Address 3, Talibon, Bohol',
                'latitude' => 10.13631,
                'longitude' => 124.32072,
                'rent_price' => 2800.00,
                'total_rooms' => 4,
                'available_rooms' => 1,
                'total_bedspaces' => 8,
                'available_bedspaces' => 2,
                'amenities' => ['WiFi', 'Electricity', 'Laundry Area'],
                'rules' => 'Curfew is 10:00 PM. Maintain cleanliness inside the boarding house.',
            ],
        ];

        foreach ($sampleBoardingHouses as $sampleBoardingHouse) {
            BoardingHouse::updateOrCreate(
                ['slug' => $sampleBoardingHouse['slug']],
                array_merge($sampleBoardingHouse, [
                    'status' => BoardingHouse::STATUS_APPROVED,
                    'is_verified' => true,
                    'verified_at' => now(),
                    'verified_by' => $admin?->id,
                    'rejection_reason' => null,
                    'deactivated_reason' => null,
                ])
            );
        }
    }
}