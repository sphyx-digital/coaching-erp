<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;

/**
 * Staff records are back-office only. Portal users (Student/Parent) can never
 * read staff data.
 */
class StaffPolicy
{
    public function viewAny(User $user): bool
    {
        return ! $user->isPortalUser() && $user->can('settings.view');
    }

    public function view(User $user, Staff $staff): bool
    {
        return ! $user->isPortalUser() && $user->can('settings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('settings.update');
    }

    public function update(User $user, Staff $staff): bool
    {
        return $user->can('settings.update');
    }

    public function delete(User $user, Staff $staff): bool
    {
        return $user->can('settings.update');
    }
}
