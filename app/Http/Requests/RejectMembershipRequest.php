<?php

namespace App\Http\Requests;

use App\Models\Membership;
use Illuminate\Foundation\Http\FormRequest;

class RejectMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $membership = $this->route('membership');

        return $membership instanceof Membership
            && ($this->user()?->can('reject', $membership) ?? false);
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Please provide a reason for rejection.',
        ];
    }
}
