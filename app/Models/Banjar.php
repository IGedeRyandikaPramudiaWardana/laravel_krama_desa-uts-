<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Banjar extends Model
{
    use HasFactory;

    protected $table = 'banjars';
    protected $fillable = ['nama_banjar'];

    public function kramas()
    {
        return $this->hasMany(Krama::class, 'banjar_id');
    }
}
