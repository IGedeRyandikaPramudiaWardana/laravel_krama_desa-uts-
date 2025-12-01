<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tagihan;

class TagihanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // === Tagihan untuk Budi (ID 1) ===
        Tagihan::create([
            'krama_id' => 1,
            'jenis_tagihan' => 'iuran',
            'jumlah' => 100000,
            'status' => 'pending',
            'tgl_tagihan' => now()->startOfMonth() // Bulan ini
        ]);
        Tagihan::create([
            'krama_id' => 1,
            'jenis_tagihan' => 'dedosan',
            'jumlah' => 50000,
            'status' => 'pending',
            'tgl_tagihan' => now()->startOfMonth() // Bulan ini
        ]);
        Tagihan::create([
            'krama_id' => 1,
            'jenis_tagihan' => 'iuran',
            'jumlah' => 100000,
            'status' => 'lunas',
            'tgl_tagihan' => now()->subMonth()->startOfMonth() // Bulan lalu
        ]);

        // === Tagihan untuk Siti (ID 2) "Orang Lain" ===
        Tagihan::create([
            'krama_id' => 2,
            'jenis_tagihan' => 'iuran',
            'jumlah' => 100000,
            'status' => 'pending',
            'tgl_tagihan' => now()->startOfMonth() // Bulan ini
        ]);
         Tagihan::create([
            'krama_id' => 2,
            'jenis_tagihan' => 'iuran',
            'jumlah' => 100000,
            'status' => 'belum_bayar',
            'tgl_tagihan' => now()->subMonth()->startOfMonth() // Bulan lalu
        ]);
    }
}
