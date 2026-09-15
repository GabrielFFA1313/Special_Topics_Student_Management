<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'student_number', 'first_name', 'middle_name', 'last_name', 'suffix',
        'birth_date', 'email', 'contact_number', 'address',
        'program_id', 'year_level', 'status', 'user_id',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name} {$this->suffix}");
    }
}