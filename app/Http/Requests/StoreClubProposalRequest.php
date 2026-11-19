<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClubProposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by ClubProposalPolicy in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'details' => ['required', 'string', 'max:5000'],
            'objectives' => ['required', 'string', 'max:5000'],
            'constitution' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ];
    }
}
