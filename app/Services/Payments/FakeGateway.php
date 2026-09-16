<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Str;

/**
 * Stand-in for Stripe/PayPal that never leaves the application, so the whole
 * checkout lifecycle can be exercised locally without gateway credentials.
 */
class FakeGateway implements PaymentGateway
{
    public function __construct(private readonly string $name) {}

    public function createCheckout(Payment $payment, string $description, string $successUrl, string $cancelUrl): PaymentResult
    {
        return new PaymentResult(
            successful: true,
            redirectUrl: route('payments.mock', $payment),
            gatewayReference: 'mock_'.$this->name.'_'.Str::lower(Str::random(20)),
            status: 'pending',
            raw: ['mock' => true, 'gateway' => $this->name, 'description' => $description],
        );
    }

    public function capture(Payment $payment, array $requestData): PaymentResult
    {
        $outcome = $requestData['mock_result'] ?? 'succeeded';
        $succeeded = $outcome === 'succeeded';

        return new PaymentResult(
            successful: $succeeded,
            gatewayReference: $payment->gateway_reference,
            status: $succeeded ? 'succeeded' : 'failed',
            message: $succeeded ? null : 'Mock gateway declined the payment.',
            raw: ['mock' => true, 'gateway' => $this->name, 'outcome' => $outcome],
        );
    }
}
