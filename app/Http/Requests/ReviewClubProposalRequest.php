<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewClubProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by ClubProposalPolicy in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
