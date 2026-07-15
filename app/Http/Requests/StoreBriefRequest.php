<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBriefRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeamLead() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'done' => ['required', 'string', 'max:10000'],
            'in_progress' => ['required', 'string', 'max:10000'],
            'blocker' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
