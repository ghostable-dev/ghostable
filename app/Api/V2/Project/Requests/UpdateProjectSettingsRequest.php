<?php

declare(strict_types=1);

namespace App\Api\V2\Project\Requests;

use App\Project\Models\Project;
use App\Project\Rules\ProjectRules;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProjectSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return ProjectRules::updateRules(
            $project instanceof Project ? $project : new Project
        );
    }
}
