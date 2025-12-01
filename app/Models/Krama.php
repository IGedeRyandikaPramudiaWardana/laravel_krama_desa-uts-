<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Krama extends Model
{
    use HasFactory;
    protected $table = 'kramas';
    protected $fillable = ['nik', 'name', 'gender', 'status_krama', 'banjar_id','status_verifikasi', 'created_by'];

    // Relasi: 1 Krama (mungkin) punya 1 Akun User
    public function user()
    {
        return $this->hasOne(User::class, 'krama_id');
    }

    // Relasi: 1 Krama punya BANYAK Tagihan
    public function tagihan()
    {
        return $this->hasMany(Tagihan::class, 'krama_id');
    }

    public function banjar()
    {
        return $this->belongsTo(Banjar::class, 'banjar_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
