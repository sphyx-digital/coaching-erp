<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use Illuminate\Support\Facades\Auth;

class PayslipController extends Controller
{
    public function show(Payslip $payslip)
    {
        // HR/admins see any payslip; a staff member may see only their own.
        $user = Auth::user();
        $ownStaffId = $user?->staff()->value('id');
        $allowed = $user?->hasAllBranchAccess() || ($ownStaffId && $ownStaffId === $payslip->staff_id);
        abort_unless($allowed, 403);

        $payslip->load(['staff.primaryBranch']);

        return view('documents.payslip', [
            'payslip' => $payslip,
            'institute' => current_institute(),
        ]);
    }
}
