<?php

namespace Tests\Feature;

use App\Models\DonationCampaign;
use App\Models\Event;
use App\Models\Member;
use App\Models\MembershipPlan;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipEventsDonationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_join_page_lists_active_plans(): void
    {
        MembershipPlan::query()->create(['name' => 'Individual Annual', 'slug' => 'individual-annual', 'price' => 75, 'is_active' => true]);
        MembershipPlan::query()->create(['name' => 'Retired Plan', 'slug' => 'retired-plan', 'price' => 0, 'is_active' => false]);

        $this->get('/membership/join')
            ->assertOk()
            ->assertSee('Individual Annual')
            ->assertDontSee('Retired Plan');
    }

    public function test_membership_registration_is_discarded_when_the_gateway_cannot_be_started(): void
    {
        $plan = MembershipPlan::query()->create(['name' => 'Individual Annual', 'slug' => 'individual-annual', 'price' => 75]);

        $response = $this->post('/membership/join', [
            'membership_plan_id' => $plan->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'gateway' => 'stripe',
        ]);

        $this->assertDatabaseMissing('members', ['email' => 'jane@example.com']);
        $this->assertDatabaseCount('payments', 0);
        $response->assertSessionHasErrors('gateway');
    }

    public function test_cancelling_a_payment_removes_the_pending_member(): void
    {
        $member = Member::query()->create([
            'first_name' => 'Cancel', 'last_name' => 'Me', 'email' => 'cancel@example.com',
            'membership_number' => 'AMSET-CANCEL', 'status' => 'pending',
        ]);

        $payment = $member->payments()->create([
            'gateway' => 'stripe', 'amount' => 75, 'currency' => 'usd', 'status' => 'pending',
        ]);

        $this->get("/payments/{$payment->id}/cancel")->assertOk();

        $this->assertDatabaseMissing('members', ['email' => 'cancel@example.com']);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
    }

    public function test_mock_gateway_completes_the_membership_lifecycle(): void
    {
        config(['payments.fake' => true]);

        $plan = MembershipPlan::query()->create(['name' => 'Individual Annual', 'slug' => 'individual-annual', 'price' => 75]);

        $this->post('/membership/join', [
            'membership_plan_id' => $plan->id,
            'first_name' => 'Mock', 'last_name' => 'Payer', 'email' => 'mock@example.com',
            'gateway' => 'stripe',
        ])->assertRedirect();

        $payment = Payment::query()->latest('id')->firstOrFail();

        $this->get("/payments/{$payment->id}/mock-checkout")->assertOk();
        $this->get("/payments/{$payment->id}/success?mock_result=succeeded")->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'succeeded']);
        $this->assertDatabaseHas('members', ['email' => 'mock@example.com', 'status' => 'active']);
    }

    public function test_mock_gateway_decline_removes_the_pending_member(): void
    {
        config(['payments.fake' => true]);

        $plan = MembershipPlan::query()->create(['name' => 'Individual Annual', 'slug' => 'individual-annual', 'price' => 75]);

        $this->post('/membership/join', [
            'membership_plan_id' => $plan->id,
            'first_name' => 'Mock', 'last_name' => 'Decline', 'email' => 'decline@example.com',
            'gateway' => 'paypal',
        ])->assertRedirect();

        $payment = Payment::query()->latest('id')->firstOrFail();

        $this->get("/payments/{$payment->id}/success?mock_result=declined")->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'failed']);
        $this->assertDatabaseMissing('members', ['email' => 'decline@example.com']);
    }

    public function test_mock_checkout_page_is_hidden_when_fake_gateways_are_disabled(): void
    {
        config(['payments.fake' => false]);

        $member = Member::query()->create([
            'first_name' => 'No', 'last_name' => 'Mock', 'email' => 'nomock@example.com',
            'membership_number' => 'AMSET-NOMOCK', 'status' => 'pending',
        ]);

        $payment = $member->payments()->create([
            'gateway' => 'stripe', 'amount' => 75, 'currency' => 'usd', 'status' => 'pending',
        ]);

        $this->get("/payments/{$payment->id}/mock-checkout")->assertNotFound();
    }

    public function test_membership_fee_comes_from_configuration_not_the_stored_plan_price(): void
    {
        config(['payments.fake' => true, 'payments.membership_fees.student-annual' => 19.5]);

        $plan = MembershipPlan::query()->create(['name' => 'Student Annual', 'slug' => 'student-annual', 'price' => 25]);

        $this->post('/membership/join', [
            'membership_plan_id' => $plan->id,
            'first_name' => 'Fee', 'last_name' => 'Check', 'email' => 'fee@example.com',
            'gateway' => 'stripe',
        ])->assertRedirect();

        $this->assertDatabaseHas('payments', ['amount' => 19.5]);
    }

    public function test_cancel_lapsed_memberships_command_cancels_only_active_members_past_expiration(): void
    {
        $lapsed = Member::query()->create([
            'first_name' => 'Lapsed', 'last_name' => 'Member', 'email' => 'lapsed@example.com',
            'membership_number' => 'AMSET-LAPSED', 'status' => 'active', 'expires_at' => now()->subDay(),
        ]);
        $current = Member::query()->create([
            'first_name' => 'Current', 'last_name' => 'Member', 'email' => 'current@example.com',
            'membership_number' => 'AMSET-CURRENT', 'status' => 'active', 'expires_at' => now()->addMonth(),
        ]);
        $alreadyCancelled = Member::query()->create([
            'first_name' => 'Already', 'last_name' => 'Cancelled', 'email' => 'already@example.com',
            'membership_number' => 'AMSET-CANCELLED', 'status' => 'cancelled', 'expires_at' => now()->subYear(),
        ]);

        $this->artisan('amset:cancel-lapsed-memberships')->assertSuccessful();

        $this->assertSame('cancelled', $lapsed->fresh()->status);
        $this->assertSame('active', $current->fresh()->status);
        $this->assertSame('cancelled', $alreadyCancelled->fresh()->status);
    }

    public function test_membership_directory_only_shows_active_members(): void
    {
        Member::query()->create(['first_name' => 'Active', 'last_name' => 'Member', 'email' => 'active@example.com', 'membership_number' => 'AMSET-1', 'status' => 'active']);
        Member::query()->create(['first_name' => 'Pending', 'last_name' => 'Member', 'email' => 'pending@example.com', 'membership_number' => 'AMSET-2', 'status' => 'pending']);

        $this->get('/membership/directory')
            ->assertOk()
            ->assertSee('Active Member')
            ->assertDontSee('Pending Member');
    }

    public function test_events_index_lists_upcoming_published_events(): void
    {
        Event::query()->create(['title' => 'Future Fair', 'slug' => 'future-fair', 'starts_at' => now()->addMonth(), 'is_published' => true]);
        Event::query()->create(['title' => 'Past Fair', 'slug' => 'past-fair', 'starts_at' => now()->subMonth(), 'is_published' => true]);

        $this->get('/events')
            ->assertOk()
            ->assertSee('Future Fair')
            ->assertDontSee('Past Fair');
    }

    public function test_free_event_registration_is_confirmed_without_payment(): void
    {
        $event = Event::query()->create(['title' => 'Free Meetup', 'slug' => 'free-meetup', 'starts_at' => now()->addMonth(), 'is_published' => true]);

        $response = $this->post("/events/{$event->slug}/register", [
            'name' => 'Attendee',
            'email' => 'attendee@example.com',
            'quantity' => 1,
            'gateway' => 'stripe',
        ]);

        $this->assertDatabaseHas('event_registrations', ['email' => 'attendee@example.com', 'status' => 'registered']);
        $response->assertRedirect(route('events.show', $event));
    }

    public function test_donation_form_lists_active_campaigns(): void
    {
        DonationCampaign::query()->create(['title' => 'Youth Innovation Fund', 'slug' => 'youth-innovation-fund', 'is_active' => true]);

        $this->get('/donate')->assertOk()->assertSee('Youth Innovation Fund');
    }
}
