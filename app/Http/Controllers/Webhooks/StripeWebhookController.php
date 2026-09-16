<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentFinalizer;
use App\Services\Payments\PaymentResult;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentFinalizer $finalizer): Response
    {
        $secret = config('payments.stripe.webhook_secret');
        $payload = $request->getContent();
        $signatureHeader = $request->header('Stripe-Signature', '');

        if (blank($secret) || ! $this->hasValidSignature($payload, $signatureHeader, $secret)) {
            Log::warning('Rejected Stripe webhook with invalid signature.');

            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);

        if (($event['type'] ?? null) === 'checkout.session.completed') {
            $session = $event['data']['object'] ?? [];
            $payment = Payment::query()->where('gateway_reference', $session['id'] ?? null)->first();

            if ($payment) {
                $paid = ($session['payment_status'] ?? null) === 'paid';
                $finalizer->finalize($payment, new PaymentResult(
                    successful: $paid,
                    gatewayReference: $session['id'] ?? null,
                    status: $paid ? 'succeeded' : 'failed',
                    raw: $session,
                ));
            }
        }

        return response('ok');
    }

    private function hasValidSignature(string $payload, string $header, string $secret): bool
    {
        $parts = [];

        foreach (explode(',', $header) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
            $parts[$key][] = $value;
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (! $timestamp || $signatures === []) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $signature) {
            if (hash_equals($expected, (string) $signature)) {
                return true;
            }
        }

        return false;
    }
}
