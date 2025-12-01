<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tagihans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('krama_id')->constrained('kramas')->onDelete('cascade');
            $table->enum('jenis_tagihan', ['iuran', 'dedosan', 'peturunan' ]);
            $table->decimal('jumlah', 12, 2);
            $table->enum('status', ['pending', 'lunas', 'belum_bayar'])->default('pending');
            $table->date('tgl_tagihan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tagihans');
    }
};
