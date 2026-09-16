<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDonationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'donation_campaign_id' => ['nullable', 'exists:donation_campaigns,id'],
            'donor_name' => ['required', 'string', 'max:160'],
            'donor_email' => ['required', 'email:rfc', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'frequency' => ['required', Rule::in(['one_time', 'weekly', 'monthly', 'yearly'])],
            'gateway' => ['required', Rule::in(['stripe', 'paypal'])],
        ];
    }
}
