<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicTerm extends Model
{
    protected $fillable = ['academic_year', 'semester', 'start_date', 'end_date', 'status'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function courseOfferings()
    {
        return $this->hasMany(CourseOffering::class);
    }
}