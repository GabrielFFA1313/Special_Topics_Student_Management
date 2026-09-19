<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Program extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'name', 'description', 'status'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}