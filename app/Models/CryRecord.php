<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CryRecord extends Model
{
    // Sesuaikan nama tabel jika di DB namanya 'cry_records'
    protected $table = 'cry_records';

    protected $fillable = [
        'baby_id', 
        'cry_category_id', 
        'confidence', 
        'audio_path'
    ];

    // Relasi balik ke Kategori
    public function category()
    {
        return $this->belongsTo(CryCategory::class, 'cry_category_id');
    }

    // Relasi balik ke Bayi
    public function baby()
    {
        return $this->belongsTo(Baby::class);
    }
}