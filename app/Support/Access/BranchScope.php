<?php

namespace App\Support\Access;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Branch-level record scoping. A branch-limited staff user (Branch Admin,
 * Teacher, Counsellor, Accountant) only sees rows in their assigned branches.
 *
 * Bypassed for admins (all-branch access), for guests (console, seeders,
 * queue), and for portal users (Student/Parent), who are governed by ownership
 * policies instead. A branch-limited user with no branch sees nothing.
 */
class BranchScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (! $user || $user->hasAllBranchAccess() || $user->isPortalUser()) {
            return;
        }

        $builder->whereIn($model->getTable().'.branch_id', $user->branchIds() ?: [0]);
    }
}
