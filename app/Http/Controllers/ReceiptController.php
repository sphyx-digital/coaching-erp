<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
    public function show(Payment $payment)
    {
        abort_unless(Auth::user()?->can('fee.view'), 403);

        $payment->load(['student', 'branch', 'allocations.invoice.lines']);

        return view('documents.receipt', [
            'payment' => $payment,
            'institute' => current_institute(),
        ]);
    }
}
