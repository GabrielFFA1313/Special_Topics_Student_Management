<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        // students don't get to browse the full list — they use their own record directly
        return $user->isAdmin() || $user->isRegistrar() || $user->isInstructor();
    }

    public function view(User $user, Student $student): bool
    {
        if ($user->isAdmin() || $user->isRegistrar() || $user->isInstructor()) {
            return true;
        }

        // Object-level authorization: a student may view only their own profile
        return $user->isStudent() && $student->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRegistrar();
    }

    public function update(User $user, Student $student): bool
    {
        return $user->isAdmin() || $user->isRegistrar();
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->isAdmin() || $user->isRegistrar();
    }
}