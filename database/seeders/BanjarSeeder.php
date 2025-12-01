<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Banjar;

class BanjarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Banjar::create(['id' => 1, 'nama_banjar' => 'Br. Beji']);
        Banjar::create(['id' => 2, 'nama_banjar' => 'Br. Celuk']);
        Banjar::create(['id' => 3, 'nama_banjar' => 'Br. Tusan']);
    }
}
