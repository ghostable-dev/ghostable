<?php

namespace App\Environment\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Entities\EnvLine;
use App\Environment\Services\EnvParser;
use App\Environment\Validation\Actions\ValidateEnvironment as Validate;
use App\Environment\Variable\Actions\NormalizeVariableKey;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

class ValidateEnvironment extends Controller
{
    public function __invoke(Project $project, string $name): JsonResource|JsonResponse
    {
        $env = $project->environmentOrFail($name);

        $this->authorize('view', $env);

        $vars = request()->input('vars', []);

        $parser = new EnvParser;
        $data = collect($parser->parse($vars))
            ->filter(fn (EnvLine $line) => $line->isValid())
            ->mapWithKeys(function (EnvLine $line) {
                $key = app(NormalizeVariableKey::class)->handle($line->key ?? '');

                return [$key => $line->value];
            })
            ->toArray();

        try {
            app(Validate::class)->handle($env, $data);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json();
    }
}
