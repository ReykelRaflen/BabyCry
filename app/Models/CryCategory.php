<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryCategory extends Model
{
    // Mengizinkan pengisian kolom 'nama'
    protected $fillable = ['nama'];

    // Relasi: Satu kategori memiliki banyak riwayat tangisan
    public function records()
    {
        return $this->hasMany(CryRecord::class);
    }
}