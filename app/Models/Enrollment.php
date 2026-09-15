<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = ['student_id', 'course_offering_id', 'enrollment_date', 'status'];

    protected function casts(): array
    {
        return ['enrollment_date' => 'date'];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function grade()
    {
        return $this->hasOne(Grade::class);
    }
}