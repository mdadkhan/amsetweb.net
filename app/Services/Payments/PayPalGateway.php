<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalGateway implements PaymentGateway
{
    public function createCheckout(Payment $payment, string $description, string $successUrl, string $cancelUrl): PaymentResult
    {
        $token = $this->accessToken();

        if ($token === null) {
            return new PaymentResult(successful: false, message: 'PayPal is not configured. Set PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET in the environment.');
        }

        $response = Http::withToken($token)->post($this->apiBase().'/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'description' => $description,
                'custom_id' => (string) $payment->id,
                'amount' => [
                    'currency_code' => strtoupper($payment->currency),
                    'value' => number_format((float) $payment->amount, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ],
        ]);

        if ($response->failed()) {
            Log::warning('PayPal order creation failed', ['response' => $response->json()]);

            return new PaymentResult(successful: false, message: 'Unable to start the PayPal order.', raw: (array) $response->json());
        }

        $order = $response->json();
        $approveUrl = collect($order['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        return new PaymentResult(
            successful: true,
            redirectUrl: $approveUrl,
            gatewayReference: $order['id'] ?? null,
            status: 'pending',
            raw: $order,
        );
    }

    public function capture(Payment $payment, array $requestData): PaymentResult
    {
        $orderId = $requestData['token'] ?? $payment->gateway_reference;
        $token = $this->accessToken();

        if ($token === null || blank($orderId)) {
            return new PaymentResult(successful: false, message: 'Missing PayPal order reference.');
        }

        $response = Http::withToken($token)->post($this->apiBase()."/v2/checkout/orders/{$orderId}/capture");

        if ($response->failed()) {
            return new PaymentResult(successful: false, message: 'Unable to capture the PayPal payment.', raw: (array) $response->json());
        }

        $order = $response->json();
        $completed = ($order['status'] ?? null) === 'COMPLETED';

        return new PaymentResult(
            successful: $completed,
            gatewayReference: $order['id'] ?? $orderId,
            status: $completed ? 'succeeded' : 'failed',
            raw: $order,
        );
    }

    private function accessToken(): ?string
    {
        $clientId = config('payments.paypal.client_id');
        $secret = config('payments.paypal.client_secret');

        if (blank($clientId) || blank($secret)) {
            return null;
        }

        $response = Http::asForm()
            ->withBasicAuth($clientId, $secret)
            ->post($this->apiBase().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if ($response->failed()) {
            Log::warning('PayPal OAuth token request failed', ['response' => $response->json()]);

            return null;
        }

        return $response->json('access_token');
    }

    private function apiBase(): string
    {
        return config('payments.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}
