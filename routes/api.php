<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TagihanController;
use App\Http\Controllers\Api\PembayaranController;
use App\Http\Controllers\Api\KramaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BanjarController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// === 1. AUTHENTICATION ===
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']); // <-- Tambahan untuk RegisterPage.jsx
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::get('/banjar', [BanjarController::class, 'index']);

// === 2. PUBLIC / CUSTOMER ===
// Route untuk UserDashboard.jsx & Tagihan.jsx (Cari NIK)
Route::get('/cari-krama-nik/{nik}', [TagihanController::class, 'cariByNik']);
Route::get('/tagihan/user/{krama_id}', [TagihanController::class, 'getByKrama']);
Route::get('/pembayaran/nik/{nik}', [PembayaranController::class, 'getRiwayatByNik']);


// === 3. PROTECTED ROUTES (Harus Login) ===
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    // Pembayaran (FormPembayaran.jsx)
    Route::post('/pembayaran', [PembayaranController::class, 'store']);
    Route::apiResource('/krama', KramaController::class);
});

// === 4. ADMIN ROUTES ===
Route::middleware(['auth:sanctum', 'admin'])->group(function () { // Hapus prefix 'admin' agar sesuai frontend
    
    // Master Data
    // Route::get('/banjar', [BanjarController::class, 'index']);
    // Route::apiResource('/krama', KramaController::class); // Menghandle index, store, show, update, destroy
    // Di dalam group middleware admin
    Route::patch('/krama/{id}/verify', [KramaController::class, 'verifyKrama']);
    // Tagihan (Laporan.jsx & BuatTagihan.jsx)
    Route::get('/tagihan', [TagihanController::class, 'index']);
    Route::post('/tagihan', [TagihanController::class, 'store']); 
    // Route::delete('/tagihan/reset', [TagihanController::class, 'reset']); // <-- Tambahan untuk tombol Reset
    Route::delete('/tagihan/{id}', [TagihanController::class, 'destroy']);

    // Pembayaran (Verifikasi.jsx)
    Route::get('/pembayaran/pending', [PembayaranController::class, 'getPending']);
    Route::patch('/pembayaran/verifikasi/{id}', [PembayaranController::class, 'verify']);
});