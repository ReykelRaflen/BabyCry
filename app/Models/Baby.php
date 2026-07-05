<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Baby extends Model
{
    use HasFactory;

    // Izinkan kolom-kolom ini diisi massal
    protected $fillable = [
        'nama', 
        'gender', 
        'date_of_birth', 
        'user_id'
    ];

    // Relasi ke User (Owner/Parent)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke tabel History (Riwayat Tangisan)
    public function histories()
    {
        return $this->hasMany(History::class);
    }
}