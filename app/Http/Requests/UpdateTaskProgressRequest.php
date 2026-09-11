<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('task'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'progress_done' => ['nullable', 'string', 'max:5000'],
            'progress_next' => ['nullable', 'string', 'max:5000'],
            'blocker_note' => ['nullable', 'string', 'max:5000', Rule::requiredIf(
                fn () => $this->input('status') === TaskStatus::Blocked->value
            )],
        ];
    }
}
