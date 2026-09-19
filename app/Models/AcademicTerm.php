<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class AcademicTerm extends Model
{
    use HasFactory;
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