<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Krama; // <-- PERBAIKAN: Tambahkan ini
use App\Mail\VerificationEmail; // <-- PERBAIKAN: Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail; // <-- PERBAIKAN: Tambahkan ini

class AuthController extends Controller
{
    public function login(Request $request) 
    {
        // 1. Validasi Input
        $request->merge([
            'login_field' => $request->input('login', $request->input('email', $request->input('nik')))
        ]);

        $request->validate([
            'login_field' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginValue = $request->login_field;

        // 2. Deteksi tipe input (Email atau NIK)
        $loginType = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'nik';
        
        $user = null;

        if ($loginType == 'email') {
            $user = User::where('email', $loginValue)->first();
        } else {
            $userQuery = User::whereHas('krama', function ($query) use ($loginValue) {
                $query->where('nik', $loginValue);
            });
            $user = $userQuery->first();
        }

        // 3. Verifikasi Password
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login_field' => ['Kredensial (Email/NIK atau Password) salah.'],
            ]);
        }

        // 4. (PENTING) Cek Verifikasi Email
        // Jika belum verifikasi, tolak login!
        if ($user->email_verified_at === null) {
            return response()->json([
                'message' => 'Akun belum diverifikasi. Silakan cek email Anda untuk kode OTP.'
            ], 403); // 403 Forbidden
        }
        
        // 5. Buat Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login success',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'krama' => $user->krama
            ]
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            // Data Akun
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|string|max:15',

            // Data Krama
            'name' => 'required|string|max:255',
            'nik' => 'required|string|size:16|unique:kramas,nik',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'banjar_id' => 'required|exists:banjars,id',
            'status_krama' => 'required|in:krama_desa,krama_tamiu,tamiu', // Ubah nullable jadi required agar data lengkap
        ]);

        DB::beginTransaction();

        try{
            // 1. Buat Krama
            $krama = Krama::create([
                'nik' => $validated['nik'],
                'name' => $validated['name'],
                'gender' => $validated['gender'],
                'banjar_id' => $validated['banjar_id'],
                'status_krama' => $validated['status_krama'],
                'status_verifikasi' => 'pending',
            ]);

            // 2. Generate OTP
            $otp = rand(100000, 999999);

            // 3. Buat User
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'],
                'krama_id' => $krama->id,
                'verification_code' => $otp,
                'email_verified_at' => null, // Pastikan NULL di awal
                'otp_expires_at' => now()->addMinutes(10), // OTP berlaku 10 menit
            ]);

            $krama->update([
                'created_by' => $user->id,
            ]);

            // 4. Kirim Email
            // Pastikan class VerificationEmail sudah di-import di atas
            Mail::to($user->email)->send(new VerificationEmail($otp));

            DB::commit();

            return response()->json([
                'message' => 'Registrasi berhasil! Silakan cek email untuk kode verifikasi.',
                'email' => $user->email
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal registrasi: ' . $e->getMessage()], 500);
        }
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // (PENTING) Cek apakah user ada DAN kodenya cocok
        if (!$user) {
            return response()->json(['message' => 'Email tidak ditemukan.'], 404);
        }


        if ($user->verification_code != $request->code){
            return response()->json([
                'message' => 'Kode verifikasi salah.',
                'debug_info' => [
                    'sen_by_user' => $request->code,
                    'stored_in_db' => $user->verification_code
                ]
            ], 400);
        }
        

        // Update status verified
        $user->update([
            'email_verified_at' => now(),
            'verification_code' => null, // Hapus kode agar tidak bisa dipakai ulang
        ]);

        return response()->json(['message' => 'Email berhasil diverifikasi. Silakan Login.']);
    }
    
    public function profile(Request $request)
    {
        return response()->json($request->user()->load('krama'));
    }

    public function logout(Request $request) 
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logout success']);
    }
}