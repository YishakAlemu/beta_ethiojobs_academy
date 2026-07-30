<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $fillable = ['user_id', 'field_of_study', 'institution', 'bio'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
