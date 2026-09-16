<?php

namespace Tests\Feature;

use App\Mail\ContactSubmissionReceived;
use App\Models\ContactSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_is_available(): void
    {
        $this->get('/contact-us')->assertOk()->assertSee('Contact AMSET');
    }

    public function test_valid_submission_is_stored_and_notification_is_queued(): void
    {
        Mail::fake();

        $response = $this->post('/contact-us', [
            'inquiry_type' => 'membership',
            'name' => 'Ada Engineer',
            'email' => 'ada@example.com',
            'phone' => '555-0100',
            'organization' => 'STEM Collective',
            'message' => 'I would like to learn more about membership.',
        ]);

        $response->assertRedirect(route('contact.create'))->assertSessionHas('status');
        $this->assertDatabaseHas(ContactSubmission::class, ['email' => 'ada@example.com', 'inquiry_type' => 'membership']);
        Mail::assertQueued(ContactSubmissionReceived::class, fn ($mail) => $mail->hasTo(config('amset.contact_email')));
    }

    public function test_invalid_submission_is_rejected(): void
    {
        $this->from('/contact-us')->post('/contact-us', [
            'inquiry_type' => 'unknown',
            'name' => '',
            'email' => 'not-an-email',
            'message' => 'short',
        ])->assertRedirect('/contact-us')->assertSessionHasErrors(['inquiry_type', 'name', 'email', 'message']);

        $this->assertDatabaseCount('contact_submissions', 0);
    }
}
