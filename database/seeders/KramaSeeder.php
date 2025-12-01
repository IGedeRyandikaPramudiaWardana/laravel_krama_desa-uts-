<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Krama;

class KramaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Krama::create([ // Krama ID 1
            'nik' => '111111',
            'name' => 'Made Blabar',
            'gender' => 'Laki-laki',
            'status_krama' => 'krama_desa',
            'banjar_id' => 1,
            'status_verifikasi' => 'terverifikasi',
        ]);
        
        Krama::create([ // Krama ID 2
            'nik' => '222222',
            'name' => 'Wayan Suparni',
            'gender' => 'Perempuan',
            'banjar_id' => 2,
            'status_krama' => 'krama_tamiu'
        ]);

        Krama::create([ // Krama ID 2
            'nik' => '333333',
            'name' => 'Nengah Honda',
            'gender' => 'Laki-laki',
            'banjar_id' => 3,
            'status_krama' => 'tamiu'
        ]);
    }
}
