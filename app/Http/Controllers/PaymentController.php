<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\PaymentFinalizer;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\PaymentResult;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentManager $payments,
        private readonly PaymentFinalizer $finalizer,
    ) {}

    public function success(Request $request, Payment $payment): View
    {
        if ($payment->status === 'pending' && $payment->gateway) {
            $result = $this->payments->gateway($payment->gateway)->capture($payment, $request->query());
            $this->finalizer->finalize($payment, $result);
        }

        return view('payments.result', ['payment' => $payment->fresh(), 'outcome' => 'success']);
    }

    public function cancel(Payment $payment): View
    {
        if ($payment->status === 'pending') {
            $this->finalizer->finalize($payment, new PaymentResult(successful: false, status: 'failed'));
        }

        return view('payments.result', ['payment' => $payment->fresh(), 'outcome' => 'cancelled']);
    }
}
