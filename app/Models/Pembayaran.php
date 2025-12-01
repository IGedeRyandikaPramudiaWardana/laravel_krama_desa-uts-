<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pembayaran extends Model
{
    use HasFactory;
    protected $table = 'pembayarans';
    protected $fillable = [
        'user_id', 'total_bayar', 'metode_pembayaran', 'tgl_bayar', 'transaction_id'
    ];
    protected $casts = ['tgl_bayar' => 'datetime'];

    // Relasi: 1 Pembayaran dimiliki oleh 1 User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi: 1 Pembayaran punya BANYAK item tagihan
    public function details()
    {
        return $this->hasMany(PembayaranDetail::class, 'pembayaran_id');
    }
}
