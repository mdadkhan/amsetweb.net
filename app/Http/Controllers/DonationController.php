<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDonationRequest;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Services\Payments\PaymentManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DonationController extends Controller
{
    public function __construct(private readonly PaymentManager $payments) {}

    public function create(): View
    {
        return view('donate', [
            'campaigns' => DonationCampaign::query()->active()->get(),
        ]);
    }

    public function store(StoreDonationRequest $request): RedirectResponse
    {
        $donation = Donation::query()->create($request->safe()->except('gateway') + [
            'is_recurring' => $request->validated('frequency') !== 'one_time',
            'status' => 'pending',
            'next_charge_at' => $request->validated('frequency') !== 'one_time' ? now()->addMonth() : null,
        ]);

        $payment = $donation->payments()->create([
            'gateway' => $request->validated('gateway'),
            'amount' => $donation->amount,
            'currency' => config('payments.default_currency'),
            'status' => 'pending',
        ]);

        $result = $this->payments->gateway($request->validated('gateway'))->createCheckout(
            $payment,
            'AMSET donation'.($donation->campaign ? ': '.$donation->campaign->title : ''),
            route('payments.success', $payment),
            route('payments.cancel', $payment),
        );

        if (! $result->successful) {
            return back()->withErrors(['gateway' => $result->message ?? 'Unable to start the payment.']);
        }

        $payment->update(['gateway_reference' => $result->gatewayReference]);

        return redirect()->away($result->redirectUrl);
    }
}
