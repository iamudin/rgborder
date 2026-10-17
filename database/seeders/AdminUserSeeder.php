<?php

namespace Database\Seeders;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@rgbarena.com');
        $adminPassword = env('ADMIN_PASSWORD', 'password');
        $adminName = env('ADMIN_NAME', 'RGB Arena Commander');
        $adminPhone = env('ADMIN_PHONE', '081234567890');

        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'phone' => $adminPhone,
                'role' => 'admin',
                'password' => Hash::make($adminPassword),
                'email_verified_at' => now(),
            ]
        );

        PlayerProfile::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'name' => 'Commander',
                'phone' => $adminPhone,
                'email' => $adminEmail,
                'is_guest' => false,
            ]
        );

        // Also seed a default customer for instant testing
        $customerEmail = env('CUSTOMER_EMAIL', 'player@rgbarena.com');
        $customerPassword = env('CUSTOMER_PASSWORD', 'password');
        $customer = User::updateOrCreate(
            ['email' => $customerEmail],
            [
                'name' => 'CyberStriker',
                'phone' => '089876543210',
                'role' => 'customer',
                'password' => Hash::make($customerPassword),
                'email_verified_at' => now(),
            ]
        );

        PlayerProfile::updateOrCreate(
            ['user_id' => $customer->id],
            [
                'name' => 'CyberStriker',
                'phone' => '089876543210',
                'email' => $customerEmail,
                'is_guest' => false,
            ]
        );
    }
}
