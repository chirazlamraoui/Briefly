<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesTeamLeadAssignments
{
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
