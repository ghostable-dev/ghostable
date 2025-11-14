<?php

declare(strict_types=1);

namespace App\Api\V2\Http\Controllers\Environment;

use App\Account\Models\User;
use App\Api\V2\Device\Presenters\DevicePresenter;
use App\Core\Http\Controllers\Controller;
use App\Environment\Models\Environment;
use App\Organization\Enums\OrganizationPermission;
use App\Project\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class GetEnvironmentDevices extends Controller
{
    public function __invoke(Project $project, string $name, DevicePresenter $presenter): JsonResponse
    {
        $environment = $project->environmentOrFail($name);

        $this->authorize('perform', [$environment, OrganizationPermission::ViewVariables]);

        $organization = $environment->owningOrganization();

        $members = $organization->users()
            ->with(['devices' => fn ($query) => $query
                ->where('active', true)
                ->whereNull('revoked_at'),
            ])
            ->get();

        $devices = [];

        foreach ($members as $member) {
            if (! $this->userCanAccessEnvironment($member, $environment)) {
                continue;
            }

            foreach ($member->devices as $device) {
                $resource = $presenter->present($device, [
                    'name',
                    'public_key',
                    'platform',
                    'status',
                    'last_seen_at',
                    'created_at',
                    'user_id',
                ]);

                $resource['data']['relationships'] = [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => (string) $member->getKey(),
                        ],
                        'attributes' => [
                            'name' => $member->name,
                            'email' => $member->email,
                        ],
                    ],
                ];

                $devices[] = $resource['data'];
            }
        }

        return response()->json([
            'data' => $devices,
            'meta' => [
                'count' => count($devices),
            ],
        ]);
    }

    private function userCanAccessEnvironment(User $user, Environment $environment): bool
    {
        $gate = Gate::forUser($user);

        foreach ([
            OrganizationPermission::ViewVariables,
            OrganizationPermission::EditVariables,
            OrganizationPermission::PushFile,
        ] as $permission) {
            if ($gate->allows('perform', [$environment, $permission])) {
                return true;
            }
        }

        return false;
    }
}
