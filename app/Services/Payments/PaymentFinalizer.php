<?php

namespace App\Services\Payments;

use App\Mail\DonationReceipt;
use App\Models\Donation;
use App\Models\EventRegistration;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PaymentFinalizer
{
    public function finalize(Payment $payment, PaymentResult $result): void
    {
        if ($payment->status !== 'pending') {
            return;
        }

        $payment->update([
            'status' => $result->status ?? ($result->successful ? 'succeeded' : 'failed'),
            'gateway_reference' => $result->gatewayReference ?? $payment->gateway_reference,
            'payment_method' => $payment->gateway,
            'paid_at' => $result->successful ? now() : null,
            'meta' => $result->raw,
        ]);

        if (! $result->successful) {
            $this->discardUnpaidMembership($payment);

            return;
        }

        $payable = $payment->payable;

        match (true) {
            $payable instanceof Member => $this->activateMembership($payable),
            $payable instanceof EventRegistration => $payable->update(['status' => 'registered']),
            $payable instanceof Donation => $this->completeDonation($payable),
            default => null,
        };
    }

    /**
     * A membership that never got paid for is removed entirely; renewals of an
     * already active/expired member are left untouched.
     */
    private function discardUnpaidMembership(Payment $payment): void
    {
        $payable = $payment->payable;

        if ($payable instanceof Member && $payable->status === 'pending') {
            $payable->delete();
        }
    }

    private function activateMembership(Member $member): void
    {
        $expires = $member->expires_at && $member->expires_at->isFuture() ? $member->expires_at : now();

        $member->update([
            'status' => 'active',
            'joined_at' => $member->joined_at ?? now(),
            'last_renewed_at' => now(),
            'expires_at' => $expires->copy()->addYear(),
            'renewal_reminder_sent' => false,
        ]);
    }

    private function completeDonation(Donation $donation): void
    {
        $donation->update([
            'status' => 'completed',
            'receipt_number' => $donation->receipt_number ?? 'DON-'.now()->format('Y').'-'.Str::upper(Str::random(8)),
        ]);

        $donation->campaign?->increment('raised_amount', $donation->amount);

        Mail::to($donation->donor_email)->queue(new DonationReceipt($donation->fresh()));
    }
}
