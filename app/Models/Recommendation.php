<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $fillable = ['cry_category_id', 'isi'];

    public function category()
    {
        return $this->belongsTo(CryCategory::class, 'cry_category_id');
    }
}