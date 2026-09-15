<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'status'];

    protected $hidden = ['password', 'remember_token']; // never expose these in JSON

    protected function casts(): array
    {
        return ['password' => 'hashed']; // Laravel auto-hashes on assignment
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function courseOfferings()
    {
        return $this->hasMany(CourseOffering::class, 'instructor_id');
    }

    public function isAdmin(): bool { return $this->role === 'administrator'; }
    public function isRegistrar(): bool { return $this->role === 'registrar'; }
    public function isInstructor(): bool { return $this->role === 'instructor'; }
    public function isStudent(): bool { return $this->role === 'student'; }
}