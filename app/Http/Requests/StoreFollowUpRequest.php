<?php

namespace App\Http\Requests;

use App\Models\FollowUp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'follow_up_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(FollowUp::STATUSES)],
        ];
    }
}
