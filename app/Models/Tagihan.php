<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tagihan extends Model
{
    use HasFactory;
    protected $table = 'tagihans';
    protected $fillable = [
        'krama_id', 'jenis_tagihan', 'jumlah', 'status', 'tgl_tagihan', 'created_by'
    ];
    protected $casts = ['tgl_tagihan' => 'date'];

    public function krama()
    {
        return $this->belongsTo(Krama::class, 'krama_id');
    }

    // Tambahkan ini untuk akses ke pembayaran
    public function pembayaranDetail()
    {
        return $this->hasOne(PembayaranDetail::class, 'tagihan_id');
    }
}