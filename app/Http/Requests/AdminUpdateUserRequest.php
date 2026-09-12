<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminUpdateUserRequest extends FormRequest
{
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $teamIds = collect($this->input('team_ids', []))->map(fn ($id) => (int) $id)->all();
            $teamLeadIds = collect($this->input('team_lead_ids', []))->map(fn ($id) => (int) $id);

            if ($teamLeadIds->contains(fn (int $teamLeadId) => ! in_array($teamLeadId, $teamIds, true))) {
                $validator->errors()->add('team_lead_ids', __('Team lead assignments must belong to selected teams.'));
            }
        });
    }
}
