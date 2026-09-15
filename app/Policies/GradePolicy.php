<?php

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;

class GradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRegistrar() || $user->isInstructor();
    }

    public function view(User $user, Grade $grade): bool
    {
        if ($user->isAdmin() || $user->isRegistrar()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $grade->enrollment->courseOffering->instructor_id === $user->id;
        }

        if ($user->isStudent()) {
            return $grade->enrollment->student->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Admin/Registrar always allowed; Instructor allowed in principle,
        // but scoped to their own offering — checked in the controller,
        // since we don't have a Grade instance yet at creation time.
        return $user->isAdmin() || $user->isRegistrar() || $user->isInstructor();
    }

    public function update(User $user, Grade $grade): bool
    {
        if ($user->isAdmin() || $user->isRegistrar()) {
            return true;
        }

        if ($user->isInstructor()) {
            return $grade->enrollment->courseOffering->instructor_id === $user->id;
        }

        return false;
    }
}