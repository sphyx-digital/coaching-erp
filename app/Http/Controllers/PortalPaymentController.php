<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Payments\OnlinePaymentService;
use App\Support\Portal\PortalAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Portal "pay now". Gated by the online_payments flag and portal ownership.
 * With the real gateway this hands off to checkout; the fake driver completes
 * immediately so the demo flow works end to end.
 */
class PortalPaymentController extends Controller
{
    public function pay(Invoice $invoice, OnlinePaymentService $service)
    {
        abort_unless(feature('online_payments'), 404);
        $user = Auth::user();
        abort_unless($user?->isPortalUser()
            && app(PortalAccess::class)->students($user)->contains('id', $invoice->student_id), 403);

        $order = $service->initiate($invoice);

        if ($service->gateway()->name() === 'fake') {
            // Simulate a captured webhook so the demo completes the payment.
            $body = json_encode([
                'status' => 'captured',
                'payment_id' => 'pay_'.Str::random(14),
                'order_id' => $order['order_id'],
                'invoice_id' => $invoice->id,
                'amount' => $invoice->balance,
            ]);
            $result = $service->handleWebhook($body, (string) config('payments.webhook_secret', 'valid'));

            return redirect('/portal/fees')->with('ok', 'Payment successful. Receipt '.($result['receipt'] ?? ''));
        }

        // Real gateway: a checkout view would use $order here.
        return redirect('/portal/fees')->with('ok', 'Payment initiated.');
    }
}
