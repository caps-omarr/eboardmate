<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EBoardMateUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@eboardmate.test'],
            [
                'name' => 'E-BoardMate Super Admin',
                'phone' => '09123456789',
                'password' => Hash::make('EBoardMate2026!'),
                'role' => User::ROLE_SUPER_ADMIN,
                'status' => User::STATUS_ACTIVE,
            ]
        );

        User::updateOrCreate(
            ['email' => 'owner@eboardmate.test'],
            [
                'name' => 'Sample Boarding House Owner',
                'phone' => '09987654321',
                'password' => Hash::make('EBoardMate2026!'),
                'role' => User::ROLE_OWNER,
                'status' => User::STATUS_ACTIVE,
            ]
        );
    }
}