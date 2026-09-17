<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTeamLeadAssignments;
use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateUserRequest extends FormRequest
{
    use ValidatesTeamLeadAssignments;

    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'team_ids' => ['required', 'array', 'min:1'],
            'team_ids.*' => ['integer', 'exists:teams,id'],
            'team_lead_ids' => ['nullable', 'array'],
            'team_lead_ids.*' => ['integer', 'exists:teams,id'],
        ];
    }
}
