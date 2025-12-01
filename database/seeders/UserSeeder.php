<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin User (Langsung Verifikasi)
        User::create([
            'name' => 'Admin Desa',
            'email' => 'admin@desa.com',
            'phone' => '081234567890',
            'password' => bcrypt('password'), 
            'role' => 'admin',
            'krama_id' => null,
            'email_verified_at' => now(), // <--- PENTING: Agar bisa langsung login
        ]);

        // 2. Krama User (Made Blabar - Contoh Warga)
        User::create([
            'name' => 'Made Blabar',
            'email' => 'madeblabar123@gmail.com',
            'phone' => '08987654321',
            'password' => bcrypt('password'),
            'role' => 'krama',
            'krama_id' => 1, // Pastikan ID 1 ada di KramaSeeder
            'email_verified_at' => now(), // <--- PENTING: Agar bisa langsung login
        ]);
    }
}