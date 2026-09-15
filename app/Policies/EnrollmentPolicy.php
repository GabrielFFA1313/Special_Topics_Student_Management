<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRegistrar() || $user->isInstructor();
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($user->isAdmin() || $user->isRegistrar()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $enrollment->courseOffering->instructor_id === $user->id;
        }

        if ($user->isStudent()) {
            return $enrollment->student->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRegistrar();
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin() || $user->isRegistrar();
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return $user->isAdmin() || $user->isRegistrar();
    }
}