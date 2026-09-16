<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RenewMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'exists:members,email'],
            'membership_number' => ['required', 'string', 'exists:members,membership_number'],
            'gateway' => ['required', Rule::in(['stripe', 'paypal'])],
        ];
    }
}
