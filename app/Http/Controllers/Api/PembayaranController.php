<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Tagihan;
use App\Models\Krama; // Pastikan Model Krama di-import
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PembayaranController extends Controller
{
    /**
     * (USER) Simpan Pembayaran Baru (Checkout)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tagihan_id' => 'required|exists:tagihans,id',
            'jumlah_bayar' => 'required|numeric',
            'metode' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            // Kunci baris data agar tidak double payment
            $tagihan = Tagihan::lockForUpdate()->find($validated['tagihan_id']);
            
            if ($tagihan->status == 'lunas') {
                return response()->json(['message' => 'Tagihan ini sudah lunas.'], 422);
            }

            // 1. Buat Header Pembayaran
            $pembayaran = Pembayaran::create([
                'user_id' => Auth::id() ?? 1, 
                'total_bayar' => $validated['jumlah_bayar'],
                'metode_pembayaran' => $validated['metode'],
                'transaction_id' => 'TRX-' . time(),
            ]);

            // 2. Buat Detail Pembayaran (Relasi ke Tagihan)
            PembayaranDetail::create([
                'pembayaran_id' => $pembayaran->id,
                'tagihan_id' => $tagihan->id,
                'jumlah' => $tagihan->jumlah
            ]);

            // 3. Update status tagihan jadi 'pending' (menunggu verifikasi admin)
            $tagihan->update(['status' => 'pending']); 

            DB::commit();

            return response()->json(['message' => 'Pembayaran berhasil dikirim, menunggu verifikasi.'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * (ADMIN) Ambil Pembayaran Pending untuk Verifikasi
     */
    public function getPending()
    {
        // Join tabel untuk mendapatkan data lengkap (Nama Krama, Nominal, dll)
        $data = DB::table('pembayaran_details')
            ->join('tagihans', 'pembayaran_details.tagihan_id', '=', 'tagihans.id')
            ->join('pembayarans', 'pembayaran_details.pembayaran_id', '=', 'pembayarans.id')
            ->join('kramas', 'tagihans.krama_id', '=', 'kramas.id')
            ->where('tagihans.status', 'pending') // Hanya yang statusnya pending
            ->select(
                'pembayarans.id as id', // ID Pembayaran
                'tagihans.id as tagihan_id',
                'kramas.name as krama_name',
                'kramas.nik',
                'pembayarans.total_bayar as jumlah_bayar',
                'pembayarans.metode_pembayaran as metode'
            )
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'tagihan_id' => $item->tagihan_id,
                    'jumlah_bayar' => $item->jumlah_bayar,
                    'metode' => $item->metode,
                    'datakrama' => [
                        'name' => $item->krama_name
                    ],
                    'nik' => $item->nik
                ];
            });

        return response()->json($data);
    }

    /**
     * (ADMIN) Verifikasi (Setujui) Pembayaran
     */
    public function verify($id)
    {
        try {
            DB::beginTransaction();

            // Cari pembayaran beserta detail tagihannya
            $pembayaran = Pembayaran::with('details.tagihan')->find($id);
            
            if (!$pembayaran) {
                return response()->json(['message' => 'Pembayaran tidak ditemukan'], 404);
            }

            // Update semua tagihan terkait menjadi LUNAS
            foreach($pembayaran->details as $detail) {
                if ($detail->tagihan) {
                    $detail->tagihan->update(['status' => 'lunas']);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Pembayaran berhasil diverifikasi.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal verifikasi.'], 500);
        }
    }

    /**
     * (PUBLIC/USER) Melihat Riwayat Pembayaran berdasarkan NIK
     * Method ini digunakan oleh Halaman Riwayat.jsx
     */
    public function getRiwayatByNik($nik)
    {
        // 1. Cari Data Krama
        $krama = Krama::where('nik', $nik)->first();

        if (!$krama) {
            return response()->json(['message' => 'Data krama tidak ditemukan.'], 404);
        }

        // 2. Query Riwayat
        // Kita mencari Pembayaran yang detailnya terhubung ke Tagihan milik Krama ini
        $riwayat = DB::table('pembayarans')
            ->join('pembayaran_details', 'pembayarans.id', '=', 'pembayaran_details.pembayaran_id')
            ->join('tagihans', 'pembayaran_details.tagihan_id', '=', 'tagihans.id')
            ->where('tagihans.krama_id', $krama->id) // Filter milik Krama ini
            ->select(
                'pembayarans.id',
                'pembayarans.created_at',
                'pembayarans.metode_pembayaran as metode',
                'pembayarans.total_bayar as jumlah_bayar',
                'tagihans.id as tagihan_id',
                'tagihans.status as status_tagihan'
            )
            ->orderBy('pembayarans.created_at', 'desc')
            ->get();

        // 3. Format Data untuk Frontend
        $formatted = $riwayat->map(function($item) {
            return [
                'id' => $item->id,
                'created_at' => $item->created_at,
                'metode' => $item->metode,
                'tagihan_id' => $item->tagihan_id,
                'jumlah_bayar' => $item->jumlah_bayar,
                // Tentukan label status untuk frontend
                'keterangan' => ($item->status_tagihan == 'lunas') ? 'selesai' : 'pending'
            ];
        });

        return response()->json([
            'identitas' => [
                'name' => $krama->name,
                'nik' => $krama->nik
            ],
            'riwayat' => $formatted
        ]);
    }
}