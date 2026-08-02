<?php

namespace App\Http\Controllers;

use App\Services\Payments\OnlinePaymentService;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, OnlinePaymentService $service)
    {
        $signature = $request->header('X-Razorpay-Signature') ?? $request->header('X-Signature');
        $result = $service->handleWebhook($request->getContent(), $signature);

        $code = $result['status'] === 'invalid_signature' ? 400 : 200;

        return response()->json($result, $code);
    }
}
