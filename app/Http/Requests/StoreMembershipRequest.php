<?php

namespace App\Http\Requests;

use App\Models\Membership;
use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Membership::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'club_id' => [
                'required',
                'integer',
                'exists:clubs,id',
            ],

            'application_message' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'club_id.required' => 'Please select a club.',
            'club_id.exists' => 'The selected club does not exist.',
        ];
    }
}
