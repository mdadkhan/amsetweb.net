<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeGateway implements PaymentGateway
{
    private const API_BASE = 'https://api.stripe.com/v1';

    public function createCheckout(Payment $payment, string $description, string $successUrl, string $cancelUrl): PaymentResult
    {
        $secretKey = config('payments.stripe.secret');

        if (blank($secretKey)) {
            return new PaymentResult(successful: false, message: 'Stripe is not configured. Set STRIPE_SECRET_KEY in the environment.');
        }

        $response = Http::asForm()
            ->withToken($secretKey)
            ->post(self::API_BASE.'/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl,
                'client_reference_id' => $payment->id,
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => $payment->currency,
                        'unit_amount' => (int) round(((float) $payment->amount) * 100),
                        'product_data' => ['name' => $description],
                    ],
                ]],
            ]);

        if ($response->failed()) {
            Log::warning('Stripe checkout session creation failed', ['response' => $response->json()]);

            return new PaymentResult(successful: false, message: 'Unable to start the Stripe checkout session.', raw: (array) $response->json());
        }

        $session = $response->json();

        return new PaymentResult(
            successful: true,
            redirectUrl: $session['url'] ?? null,
            gatewayReference: $session['id'] ?? null,
            status: 'pending',
            raw: $session,
        );
    }

    public function capture(Payment $payment, array $requestData): PaymentResult
    {
        $secretKey = config('payments.stripe.secret');
        $sessionId = $requestData['session_id'] ?? $payment->gateway_reference;

        if (blank($secretKey) || blank($sessionId)) {
            return new PaymentResult(successful: false, message: 'Missing Stripe session reference.');
        }

        $response = Http::withToken($secretKey)->get(self::API_BASE.'/checkout/sessions/'.$sessionId);

        if ($response->failed()) {
            return new PaymentResult(successful: false, message: 'Unable to verify the Stripe payment.', raw: (array) $response->json());
        }

        $session = $response->json();
        $paid = ($session['payment_status'] ?? null) === 'paid';

        return new PaymentResult(
            successful: $paid,
            gatewayReference: $session['id'] ?? $sessionId,
            status: $paid ? 'succeeded' : 'failed',
            raw: $session,
        );
    }
}
