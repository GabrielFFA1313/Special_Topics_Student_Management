<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = ['code', 'name', 'description', 'status'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}