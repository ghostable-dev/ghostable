<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Api\V2\Http\Controllers\Concerns\PresentsAuditActor;
use App\Core\Http\Controllers\Controller;
use App\Environment\Models\EnvironmentSecret;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetEnvironmentVariableContext extends Controller
{
    use PresentsAuditActor;

    public function __invoke(Request $request, Project $project, string $name, string $variable): JsonResponse
    {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::ViewVariables]);
        $this->authorize('perform', [$environment, OrganizationPermission::ViewContext]);

        /** @var EnvironmentSecret|null $secret */
        $secret = EnvironmentSecret::query()
            ->with([
                'note.createdBy',
                'note.lastUpdatedBy',
                'comments.createdBy',
            ])
            ->where('environment_id', $environment->id)
            ->where('name', $variable)
            ->first();

        abort_unless($secret, 404, 'Variable not found in this environment.');

        return response()->json([
            'data' => [
                'scope' => 'variable_context',
                'environment' => [
                    'id' => (string) $environment->id,
                    'name' => $environment->name,
                    'type' => $environment->type->value,
                ],
                'variable' => [
                    'id' => (string) $secret->getKey(),
                    'name' => $secret->name,
                    'latest_version' => (int) ($secret->version ?? 0),
                ],
                'note' => $secret->note ? $this->presentNote($secret->note) : null,
                'comments' => $secret->comments
                    ->map(fn ($comment) => $this->presentComment($comment))
                    ->values(),
                'permissions' => [
                    'edit_note' => (bool) $request->user()?->can('perform', [
                        $environment,
                        OrganizationPermission::EditNote,
                    ]),
                    'comment' => (bool) $request->user()?->can('perform', [
                        $environment,
                        OrganizationPermission::Comment,
                    ]),
                    'view_version_change_notes' => (bool) $request->user()?->can('perform', [
                        $environment,
                        OrganizationPermission::ViewVersionChangeNotes,
                    ]),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function presentNote($note): array
    {
        return [
            'id' => (string) $note->getKey(),
            'created_at' => optional($note->created_at)->toIso8601String(),
            'updated_at' => optional($note->updated_at)->toIso8601String(),
            'created_by' => $this->presentAuditActor($note->createdBy),
            'last_updated_by' => $this->presentAuditActor($note->lastUpdatedBy),
            'body' => [
                'ciphertext' => $note->ciphertext,
                'nonce' => $note->nonce,
                'alg' => $note->alg,
                'aad' => $note->aad,
                'claims' => $note->claims,
                'client_sig' => $note->client_sig,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentComment($comment): array
    {
        return [
            'id' => (string) $comment->getKey(),
            'created_at' => optional($comment->created_at)->toIso8601String(),
            'created_by' => $this->presentAuditActor($comment->createdBy),
            'body' => [
                'ciphertext' => $comment->ciphertext,
                'nonce' => $comment->nonce,
                'alg' => $comment->alg,
                'aad' => $comment->aad,
                'claims' => $comment->claims,
                'client_sig' => $comment->client_sig,
            ],
        ];
    }
}
