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
        Schema::create('kramas', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 20)->unique();
            $table->string('name');
            $table->enum('gender', ['Laki-laki', 'Perempuan']);
            $table->enum('status_krama', ['krama_desa', 'krama_tamiu', 'tamiu']);
            $table->foreignId('banjar_id')->nullable()->constrained('banjars')->onDelete('set null');
            $table->enum('status_verifikasi', ['pending', 'terverifikasi'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kramas');
    }
};
