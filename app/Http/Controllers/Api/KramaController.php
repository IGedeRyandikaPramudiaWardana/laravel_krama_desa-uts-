<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Krama;
use App\Models\User; // <-- PENTING: Import Model User
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // <-- PENTING: Import Hash
use Illuminate\Support\Facades\DB;   // <-- PENTING: Import DB

class KramaController extends Controller
{
    /**
     * Menampilkan daftar Krama.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Krama::with(['banjar', 'pembuat', 'user']); 

        // LOGIKA FILTER ROLE
        if ($user->role !== 'admin') {
            $query->where(function($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhere('id', $user->krama_id);
            });
        }

        // --- UPDATE: PENCARIAN UNIVERSAL (Nama ATAU NIK) ---
        if ($request->has('search') && $request->search != '') {
            $keyword = $request->search;
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->orWhere('nik', 'like', '%' . $keyword . '%');
            });
        }
        
        // Filter pencarian
        // if ($request->has('nik') && $request->nik != '') {
        //     $query->where('nik', 'like', '%' . $request->nik . '%');
        // }
        
        if ($request->has('status_verifikasi') && $request->status_verifikasi != '') {
            $query->where('status_verifikasi', $request->status_verifikasi);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(10));
    }

    /**
     * Menambah Krama Baru (Bisa sekaligus buat User Login jika email diisi).
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Validasi
        $validated = $request->validate([
            'nik' => 'required|string|unique:kramas,nik',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'status_krama' => 'required|in:krama_desa,krama_tamiu,tamiu',
            'banjar_id' => 'required|exists:banjars,id',
            // Validasi Email & HP (Nullable / Boleh Kosong)
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string',
        ]);

        DB::beginTransaction(); // Mulai transaksi database agar aman

        try {
            // 2. Simpan Data Krama
            $krama = Krama::create([
                'nik' => $validated['nik'],
                'name' => $validated['name'],
                'gender' => $validated['gender'],
                'status_krama' => $validated['status_krama'],
                'banjar_id' => $validated['banjar_id'],
                'created_by' => Auth::id(),
                // Jika Admin yg input -> Terverifikasi. Jika User -> Pending.
                'status_verifikasi' => (Auth::user()->role === 'admin') ? 'terverifikasi' : 'pending'
            ]);

            // 3. Cek apakah Email diisi? Jika YA, buatkan Akun User
            if (!empty($request->email)) {
                User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $request->phone,
                    // Password Default = NIK Krama
                    'password' => Hash::make($validated['nik']), 
                    'role' => 'krama',
                    'krama_id' => $krama->id,
                    'email_verified_at' => now(), // Anggap verified karena admin/user login yg buat
                ]);
            }

            DB::commit(); // Simpan permanen

            return response()->json([
                'message' => 'Data Krama berhasil ditambahkan.',
                'krama' => $krama
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan jika error
            return response()->json(['message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mengupdate data Krama (dan User jika ada).
     */
    public function update(Request $request, $id)
    {
        $krama = Krama::with('user')->find($id); // Load user terkait
        if(!$krama) return response()->json(['message'=>'Not found'], 404);
        
        // Validasi
        $rules = [
            'nik' => 'required|string|unique:kramas,nik,' . $id,
            'name' => 'required|string',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'status_krama' => 'required',
            'banjar_id' => 'nullable',
            // Validasi Email & HP
            'email' => 'nullable|email', 
            'phone' => 'nullable|string',
        ];

        // Jika Krama ini punya akun User, pastikan email unik (kecuali punya sendiri)
        if ($krama->user) {
            $rules['email'] = 'required|email|unique:users,email,' . $krama->user->id;
        }

        $validated = $request->validate($rules);
        
        // 1. Update Tabel Krama
        $krama->update([
            'nik' => $validated['nik'],
            'name' => $validated['name'],
            'gender' => $validated['gender'],
            'status_krama' => $validated['status_krama'],
            'banjar_id' => $validated['banjar_id'] ?? $krama->banjar_id,
        ]);

        // 2. Update Tabel User (Jika ada relasinya)
        if ($krama->user) {
            $krama->user->update([
                'name' => $validated['name'], // Sinkronkan nama
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);
        }

        return response()->json(['message' => 'Data berhasil diperbarui.']);
    }

    /**
     * (KHUSUS ADMIN) Memverifikasi Krama.
     */
    public function verifyKrama($id): JsonResponse
    {
        // Pastikan hanya admin (Middleware sudah handle, tapi double check boleh)
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $krama = Krama::find($id);
        if (!$krama) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $krama->update(['status_verifikasi' => 'terverifikasi']);

        return response()->json([
            'message' => 'Status Krama berhasil diverifikasi.',
            'krama' => $krama
        ]);
    }

    public function show($id)
    {
        $krama = Krama::with(['banjar', 'user'])->where('nik', $id)->orWhere('id', $id)->first();
        if (!$krama) return response()->json(['message' => 'Not found'], 404);
        return response()->json($krama);
    }

    public function destroy($id)
    {
        $krama = Krama::find($id);
        if(!$krama) return response()->json(['message'=>'Not found'], 404);
        
        // Jika ada user terkait, user juga akan terhapus jika di migration user 'onDelete cascade'
        // Jika tidak, kita bisa hapus manual:
        if($krama->user) {
            $krama->user->delete();
        }

        $krama->delete();
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}