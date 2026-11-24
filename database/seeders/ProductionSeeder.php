<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL');
        $password = (string) env('ADMIN_PASSWORD');

        if ($email === '' || $password === '') {
            throw new RuntimeException('ADMIN_EMAIL dan ADMIN_PASSWORD wajib diatur sebelum menjalankan ProductionSeeder.');
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make($password),
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        );

        foreach (['store_name', 'store_address', 'store_phone'] as $key) {
            Setting::firstOrCreate(['key' => $key], ['value' => (string) env('STORE_'.strtoupper($key), '')]);
        }
    }
}
