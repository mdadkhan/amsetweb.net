<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PaymentManager;
use Illuminate\Contracts\View\View;

class MockCheckoutController extends Controller
{
    public function __invoke(Payment $payment): View
    {
        abort_unless(PaymentManager::usingFakeGateways(), 404);

        return view('payments.mock-checkout', [
            'payment' => $payment,
            'successUrl' => route('payments.success', $payment),
            'cancelUrl' => route('payments.cancel', $payment),
        ]);
    }
}
