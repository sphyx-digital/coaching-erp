<?php

use App\Services\Fees\DiscountApprovalHandler;

/*
 |--------------------------------------------------------------------------
 | Approval handlers
 |--------------------------------------------------------------------------
 | Maps an approval action to the handler that applies the resulting state
 | change on approval (and reverts on rejection). See App\Services\Approvals.
 */

return [
    'handlers' => [
        'fee.discount' => DiscountApprovalHandler::class,
        // 'fee.refund'         => ...
        // 'enrollment.withdraw'=> ...
        // 'reportcard.publish' => ...
    ],
];
