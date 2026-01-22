<?php

declare(strict_types=1);

namespace App\Api\Integrations\V1\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Integration\Models\IntegrationToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShowOrganization extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var IntegrationToken|null $token */
        $token = $request->attributes->get('integrationToken');
        $organization = $request->attributes->get('integrationOrganization');

        if (! $token || ! $organization) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return response()->json([
            'organization' => [
                'id' => (string) $organization->getKey(),
                'name' => $organization->name,
            ],
            'integration' => [
                'client_id' => $token->integration_client_id,
                'integration_id' => $token->integration_id,
                'scopes' => $token->scopes ?? [],
            ],
        ]);
    }
}
