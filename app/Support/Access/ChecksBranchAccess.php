<?php

namespace App\Support\Access;

use App\Models\User;

trait ChecksBranchAccess
{
    /**
     * May this user act on a record in the given branch? Admins may act on any
     * branch; branch-limited staff only on their assigned branches.
     */
    protected function branchAllows(User $user, ?int $branchId): bool
    {
        if ($user->hasAllBranchAccess()) {
            return true;
        }

        return $branchId !== null && in_array($branchId, $user->branchIds(), true);
    }
}
