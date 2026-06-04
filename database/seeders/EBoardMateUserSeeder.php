<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EBoardMateUserSeeder extends Seeder
{
   
    public function run(): void
    {
        
        User::updateOrCreate(
            ['email' => env('SEED_ADMIN_EMAIL', 'admin@eboardmate.com')],
            [
                'name' => env('SEED_ADMIN_NAME', 'E-BoardMate Super Admin'),
                'phone' => env('SEED_ADMIN_PHONE', '09123456789'),
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'fallback_password_change_immediately')),
                'role' => User::ROLE_SUPER_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(), 
            ]
        );
    }
}