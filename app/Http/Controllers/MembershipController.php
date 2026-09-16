<?php

namespace App\Http\Controllers;

use App\Http\Requests\RenewMembershipRequest;
use App\Http\Requests\StoreMemberRequest;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Services\Payments\PaymentManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class MembershipController extends Controller
{
    public function __construct(private readonly PaymentManager $payments) {}

    public function create(): View
    {
        return view('membership.join', [
            'plans' => MembershipPlan::query()->active()->get(),
        ]);
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $plan = MembershipPlan::query()->findOrFail($request->validated('membership_plan_id'));

        $member = Member::query()->create($request->safe()->except('gateway') + [
            'membership_number' => $this->generateMembershipNumber(),
            'status' => 'pending',
        ]);

        return $this->startPayment($member, $plan->fee(), $request->string('gateway'), 'AMSET membership: '.$plan->name);
    }

    public function renewCreate(): View
    {
        return view('membership.renew');
    }

    public function renew(RenewMembershipRequest $request): RedirectResponse
    {
        $member = Member::query()
            ->where('email', $request->validated('email'))
            ->where('membership_number', $request->validated('membership_number'))
            ->firstOrFail();

        $amount = $member->membershipPlan?->fee() ?? 0;

        return $this->startPayment($member, $amount, $request->string('gateway'), 'AMSET membership renewal');
    }

    public function directory(): View
    {
        return view('membership.directory', [
            'members' => Member::query()->active()->orderBy('last_name')->get(),
        ]);
    }

    private function startPayment(Member $member, float $amount, string $gateway, string $description): RedirectResponse
    {
        $payment = $member->payments()->create([
            'gateway' => $gateway,
            'amount' => $amount,
            'currency' => config('payments.default_currency'),
            'status' => 'pending',
        ]);

        $result = $this->payments->gateway($gateway)->createCheckout(
            $payment,
            $description,
            route('payments.success', $payment),
            route('payments.cancel', $payment),
        );

        if (! $result->successful) {
            $payment->delete();

            if ($member->status === 'pending') {
                $member->delete();
            }

            return back()->withInput()->withErrors(['gateway' => $result->message ?? 'Unable to start the payment.']);
        }

        $payment->update(['gateway_reference' => $result->gatewayReference]);

        return redirect()->away($result->redirectUrl);
    }

    private function generateMembershipNumber(): string
    {
        return 'AMSET-'.now()->format('Y').'-'.Str::upper(Str::random(6));
    }
}
