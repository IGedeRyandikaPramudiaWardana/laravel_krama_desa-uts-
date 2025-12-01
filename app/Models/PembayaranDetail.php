<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PembayaranDetail extends Model
{
    use HasFactory;
    protected $table = 'pembayaran_details';
    protected $fillable = ['pembayaran_id', 'tagihan_id', 'jumlah'];

    // Relasi ke tabel induk
    public function pembayaran()
    {
        return $this->belongsTo(Pembayaran::class, 'pembayaran_id');
    }

    // Relasi ke tagihan yg dibayar
    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }
}
