<?php

namespace App\Services\Payments;

use App\Models\Payment;

interface PaymentGateway
{
    /**
     * Start a checkout session/order for the given payment and return a redirect URL.
     */
    public function createCheckout(Payment $payment, string $description, string $successUrl, string $cancelUrl): PaymentResult;

    /**
     * Capture/finalize a payment after the customer returns from the gateway.
     */
    public function capture(Payment $payment, array $requestData): PaymentResult;
}
