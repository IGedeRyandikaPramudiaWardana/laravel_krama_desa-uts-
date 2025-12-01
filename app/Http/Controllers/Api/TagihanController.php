<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Krama;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TagihanController extends Controller
{
    /**
     * (ADMIN) Buat Tagihan Baru (Single Input dari Dropdown)
     */
    public function store(Request $request)
    {
        // 1. Validasi Input (Sesuai form baru di React)
        $validated = $request->validate([
            'nik' => 'required|exists:kramas,nik',
            'bulan' => 'required|date',
            'jenis_tagihan' => 'required|in:iuran,dedosan,peturunan', // Pilihan Dropdown
            'jumlah' => 'required|numeric|min:1000', // Input Nominal Tunggal
        ]);

        $krama = Krama::where('nik', $validated['nik'])->first();

        try {
            DB::beginTransaction();

            // 2. Simpan Tagihan (Tanpa Loop)
            $tagihan = Tagihan::create([
                'krama_id' => $krama->id,
                'jenis_tagihan' => $validated['jenis_tagihan'],
                'jumlah' => $validated['jumlah'],
                'tgl_tagihan' => $validated['bulan'],
                'status' => 'pending', // Default: Belum Bayar
                'created_by' => Auth::id() ?? 1, // ID Admin pembuat
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Tagihan berhasil dibuat untuk ' . $krama->name,
                'tagihan' => $tagihan
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal membuat tagihan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * (PUBLIC) Logic Pencarian untuk UserDashboard & Tagihan Admin
     * Mencari data krama berdasarkan NIK untuk pembayaran.
     */
    public function cariByNik($nik)
    {
        $krama = Krama::where('nik', $nik)->first();

        if (!$krama) {
            return response()->json(['message' => 'Krama dengan NIK tersebut tidak ditemukan'], 404);
        }

        // Ambil tagihan yang belum lunas (pending / belum_bayar)
        $tagihanBelumLunas = Tagihan::where('krama_id', $krama->id)
            ->whereIn('status', ['pending', 'belum_bayar'])
            ->orderBy('tgl_tagihan', 'desc')
            ->get();

        $total = $tagihanBelumLunas->sum('jumlah');

        return response()->json([
            'identitas' => [
                'name' => $krama->name,
                'nik' => $krama->nik,
                'status' => $krama->status_krama,
                'banjar' => $krama->banjar ? $krama->banjar->nama_banjar : '-'
            ],
            'tagihan' => $tagihanBelumLunas, 
            'tagihan_terbuka' => $tagihanBelumLunas->first(), // Untuk preview di kartu
            'total_tagihan' => $total,
            'status_pembayaran' => $tagihanBelumLunas->isEmpty() ? 'Lunas' : 'Belum Bayar'
        ]);
    }

    /**
     * (ADMIN) List Laporan Tagihan
     * Digunakan di halaman Laporan.jsx
     */
    public function index()
    {
        $tagihan = Tagihan::with(['krama', 'pembayaranDetail.pembayaran'])
                          ->orderBy('created_at', 'desc')
                          ->get();
        
        // Format data agar sesuai Frontend Laporan.jsx
        // Frontend mengharapkan object: { datakrama: {...}, ... }
        $formatted = $tagihan->map(function($item) {
            return [
                'id' => $item->id,
                'datakrama' => $item->krama, 
                'bulan' => \Carbon\Carbon::parse($item->tgl_tagihan)->format('F Y'),
                'jumlah' => $item->jumlah,
                'status' => $item->status,
                'pembayaran' => $item->pembayaranDetail ? [
                    'keterangan' => $item->status == 'lunas' ? 'selesai' : 'pending'
                ] : null
            ];
        });

        return response()->json($formatted);
    }

    /**
     * (ADMIN) Reset Data (Hapus Semua Tagihan & Pembayaran)
     * HATI-HATI: Ini menghapus data transaksi!
     */
    // public function reset()
    // {
    //     try {
    //         DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    //         Tagihan::truncate();
    //         DB::table('pembayarans')->truncate();
    //         DB::table('pembayaran_details')->truncate();
    //         DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
    //         return response()->json(['message' => 'Semua data tagihan & pembayaran berhasil direset.']);
    //     } catch (\Exception $e) {
    //         return response()->json(['message' => 'Gagal reset data'], 500);
    //     }
    // }

    /**
     * (ADMIN) Hapus Satu Tagihan
     */
    public function destroy($id)
    {
        $tagihan = Tagihan::find($id);
        if(!$tagihan) return response()->json(['message' => 'Tagihan tidak ditemukan'], 404);
        
        $tagihan->delete();
        return response()->json(['message' => 'Tagihan berhasil dihapus']);
    }
}