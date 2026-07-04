<?php

namespace App\Http\Requests;

use App\Enums\UpdateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDailyUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'done' => ['required', 'string', 'max:5000'],
            'in_progress' => ['required', 'string', 'max:5000'],
            'blocker_type' => ['required', Rule::in(['none', 'existing', 'new'])],
            'blocker_id' => ['nullable', 'required_if:blocker_type,existing', 'integer', 'exists:blockers,id'],
            'new_blocker' => ['nullable', 'required_if:blocker_type,new', 'string', 'max:255'],
            'status' => ['required', Rule::enum(UpdateStatus::class)],
        ];
    }
}
