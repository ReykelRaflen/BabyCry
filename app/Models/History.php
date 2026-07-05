<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $table = 'histories';

    protected $fillable = [
        'baby_id',
        'user_id',
        'cry_category_id',
        'confidence',
        'audio_path',
    ];

    // Di dalam class History di app/Models/History.php
    public function baby()
    {
        return $this->belongsTo(Baby::class, 'baby_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
