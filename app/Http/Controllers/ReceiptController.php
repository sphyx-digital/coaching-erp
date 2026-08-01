<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Support\Portal\PortalAccess;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
    public function show(Payment $payment)
    {
        $user = Auth::user();
        $allowed = $user?->can('fee.view')
            || ($user?->isPortalUser() && app(PortalAccess::class)->students($user)->contains('id', $payment->student_id));
        abort_unless($allowed, 403);

        $payment->load(['student', 'branch', 'allocations.invoice.lines']);

        return view('documents.receipt', [
            'payment' => $payment,
            'institute' => current_institute(),
        ]);
    }
}
