<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseOffering extends Model
{
    protected $fillable = [
        'course_id', 'academic_term_id', 'instructor_id',
        'section', 'schedule', 'room', 'capacity', 'status',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}