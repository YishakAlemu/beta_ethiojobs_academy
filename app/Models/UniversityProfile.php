<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniversityProfile extends Model
{
    protected $fillable = ['user_id', 'website', 'location', 'description', 'is_verified'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}