<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    protected $fillable = ['course_code', 'course_title', 'description', 'units', 'status'];

    public function courseOfferings()
    {
        return $this->hasMany(CourseOffering::class);
    }
}