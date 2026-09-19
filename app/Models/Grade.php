<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Grade extends Model
{
    use HasFactory;
    protected $fillable = ['enrollment_id', 'midterm_grade', 'final_grade', 'remarks'];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}