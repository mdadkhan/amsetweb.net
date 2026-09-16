<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactSubmissionRequest;
use App\Mail\ContactSubmissionReceived;
use App\Models\ContactSubmission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('contact');
    }

    public function store(StoreContactSubmissionRequest $request): RedirectResponse
    {
        $submission = ContactSubmission::query()->create($request->validated());

        Mail::to(config('amset.contact_email'))->queue(new ContactSubmissionReceived($submission));

        return to_route('contact.create')->with('status', 'Thank you. Your message has been received.');
    }
}
