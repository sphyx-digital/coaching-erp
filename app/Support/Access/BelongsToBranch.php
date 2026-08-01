<?php

namespace App\Support\Access;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Applied to branch-owned models. Adds the branch relation and the global
 * BranchScope, so branch-limited users only ever read rows in their branches.
 *
 * Use withoutGlobalScope(BranchScope::class) for deliberate cross-branch admin
 * reporting.
 */
trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::addGlobalScope(new BranchScope());
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
