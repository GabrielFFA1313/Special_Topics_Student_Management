<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // any authenticated role may browse/list programs
    }

    public function view(User $user, Program $program): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRegistrar();
    }

    public function update(User $user, Program $program): bool
    {
        return $user->isAdmin() || $user->isRegistrar();
    }

    public function delete(User $user, Program $program): bool
    {
        return $user->isAdmin() || $user->isRegistrar();
    }
}