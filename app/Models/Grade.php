<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['enrollment_id', 'midterm_grade', 'final_grade', 'remarks'];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}