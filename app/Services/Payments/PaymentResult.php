<?php

namespace App\Services\Payments;

readonly class PaymentResult
{
    public function __construct(
        public bool $successful,
        public ?string $redirectUrl = null,
        public ?string $gatewayReference = null,
        public ?string $status = null,
        public ?string $message = null,
        public array $raw = [],
    ) {}
}
