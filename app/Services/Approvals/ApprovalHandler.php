<?php

namespace App\Services\Approvals;

use App\Models\Approval;

/**
 * Applies the state change that follows a decision. Registered per action in
 * config/approvals.php. handleApproved runs the effect; handleRejected reverts
 * cleanly to the prior state.
 */
interface ApprovalHandler
{
    public function handleApproved(Approval $approval): void;

    public function handleRejected(Approval $approval): void;
}
