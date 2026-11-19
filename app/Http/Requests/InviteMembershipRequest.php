<?php

namespace App\Http\Requests;

use App\Models\Club;
use App\Models\User;
use App\Models\Membership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $club = $this->route('club');

        return $club instanceof Club
            && ($this->user()?->can('invite',
            [Membership::class, $club]
        ) ?? false);
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('system_role', User::ROLE_STUDENT),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Please select a student.',
            'user_id.exists' => 'The selected student account is invalid.',
        ];
    }
}
