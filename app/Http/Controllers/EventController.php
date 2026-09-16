<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRegistrationRequest;
use App\Models\Event;
use App\Models\EventTicket;
use App\Services\Payments\PaymentManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function __construct(private readonly PaymentManager $payments) {}

    public function index(): View
    {
        return view('events.index', [
            'events' => Event::query()->published()->upcoming()->with('tickets')->get(),
        ]);
    }

    public function show(Event $event): View
    {
        return view('events.show', [
            'event' => $event->load('tickets'),
        ]);
    }

    public function register(StoreEventRegistrationRequest $request, Event $event): RedirectResponse
    {
        if (! $event->registrationOpen()) {
            return back()->withErrors(['event' => 'Registration for this event is closed.']);
        }

        $ticket = $request->validated('event_ticket_id')
            ? EventTicket::query()->where('event_id', $event->id)->findOrFail($request->validated('event_ticket_id'))
            : null;

        $quantity = (int) $request->validated('quantity');
        $amountDue = (float) ($ticket->price ?? 0) * $quantity;

        $registration = $event->registrations()->create($request->safe()->only(['event_ticket_id', 'name', 'email', 'phone']) + [
            'confirmation_code' => Str::upper(Str::random(10)),
            'quantity' => $quantity,
            'amount_due' => $amountDue,
            'status' => $amountDue > 0 ? 'pending' : 'registered',
        ]);

        if ($amountDue <= 0) {
            return to_route('events.show', $event)->with('status', 'You are registered for '.$event->title.'. Confirmation code: '.$registration->confirmation_code);
        }

        $payment = $registration->payments()->create([
            'gateway' => $request->validated('gateway'),
            'amount' => $amountDue,
            'currency' => config('payments.default_currency'),
            'status' => 'pending',
        ]);

        $result = $this->payments->gateway($request->validated('gateway'))->createCheckout(
            $payment,
            'AMSET event registration: '.$event->title,
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
