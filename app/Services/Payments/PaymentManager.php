<?php

namespace App\Services\Payments;

use InvalidArgumentException;

class PaymentManager
{
    public function gateway(string $name): PaymentGateway
    {
        return match ($name) {
            'stripe' => self::usingFakeGateways() ? new FakeGateway($name) : new StripeGateway,
            'paypal' => self::usingFakeGateways() ? new FakeGateway($name) : new PayPalGateway,
            default => throw new InvalidArgumentException("Unknown payment gateway [{$name}]."),
        };
    }

    public static function usingFakeGateways(): bool
    {
        return (bool) config('payments.fake') && ! app()->isProduction();
    }
}
