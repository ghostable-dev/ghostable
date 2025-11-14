<?php

declare(strict_types=1);

namespace App\Api\V1\Http\Controllers\Environment;

use App\Api\Core\Resources\Environment\EnvironmentVariableResource;
use App\Api\V2\Environment\Presenters\EnvironmentKeyPresenter;
use App\Core\Http\Controllers\Controller;
use App\Environment\Actions\ResolveEnvironmentVariables;
use App\Environment\Actions\ResolveLatestEnvironmentKey;
use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Validation\Actions\ValidateEnvironment as Validate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;
use JsonException;

class DeployEnvironment extends Controller
{
    /**
     * Validate and return environment variables for deployment.
     */
    public function __invoke(): JsonResponse|JsonResource
    {
        $environment = $this->resolveEnvironmentFromToken();

        try {
            $this->validate($environment);
        } catch (ValidationException $e) {
            return $this->validationErrors($e);
        }

        $deploymentToken = $this->resolveDeploymentToken($environment);

        $vars = resolve(ResolveEnvironmentVariables::class)->handle($environment);
        $environmentKey = resolve(ResolveLatestEnvironmentKey::class)->handle($environment);

        $presentedEnvironmentKey = $this->presentEnvironmentKey($environmentKey, $deploymentToken);

        return response()->json([
            'environment_key' => $presentedEnvironmentKey,
            'secrets' => EnvironmentVariableResource::collection($vars)->toArray(request()),
        ]);
    }

    protected function presentEnvironmentKey(
        ?EnvironmentKey $environmentKey,
        ?DeploymentToken $deploymentToken = null
    ): ?array {
        if (! $environmentKey || $environmentKey->rotated_at) {
            return null;
        }

        $environmentKey->loadMissing('envelope');

        $envelope = $environmentKey->envelope;

        if (! $envelope || $envelope->isInactive()) {
            return null;
        }

        $recipients = $envelope->recipients;

        if ($deploymentToken) {
            $recipients = $this->filterRecipientsForDeploymentToken($recipients, $deploymentToken);
        } elseif (is_array($recipients)) {
            $recipients = array_values($recipients);
        }

        $fromEphemeralPublicKey = $this->extractFromEphemeralPublicKey($recipients);

        $resource = app(EnvironmentKeyPresenter::class)->present($environmentKey);
        $resource = $this->applyEnvelopeMetadataToResource($resource, $recipients, $fromEphemeralPublicKey);

        return $resource;
    }

    protected function resolveEnvironmentFromToken(): Environment
    {
        $actor = request()->user();

        if (! $actor instanceof Environment || ! $actor->tokenCan('deploy')) {
            throw new AuthorizationException(
                'The provided token is invalid, lacks deploy scope, or does not correspond to an environment.'
            );
        }

        return $actor;
    }

    protected function resolveDeploymentToken(Environment $environment): ?DeploymentToken
    {
        $accessToken = $environment->currentAccessToken();

        if (! $accessToken) {
            return null;
        }

        return DeploymentToken::query()
            ->where('personal_access_token_id', $accessToken->getKey())
            ->first();
    }

    protected function validate(Environment $environment): void
    {
        app(Validate::class)->handle($environment);
    }

    protected function validationErrors(ValidationException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], 422);
    }

    protected function filterRecipientsForDeploymentToken(
        mixed $recipients,
        ?DeploymentToken $deploymentToken
    ): ?array {
        if (! is_array($recipients) || ! $deploymentToken) {
            return is_array($recipients) ? array_values($recipients) : $recipients;
        }

        $filtered = array_values(array_filter(
            $recipients,
            function ($recipient) use ($deploymentToken): bool {
                if (! is_array($recipient)) {
                    return false;
                }

                $type = strtolower((string) ($recipient['type'] ?? ''));

                if ($type !== 'deployment') {
                    return false;
                }

                $id = (string) ($recipient['id'] ?? '');

                return $id === (string) $deploymentToken->getKey();
            }
        ));

        return $filtered ?: null;
    }

    protected function extractFromEphemeralPublicKey(mixed $recipients): ?string
    {
        if (! is_array($recipients)) {
            return null;
        }

        foreach ($recipients as $recipient) {
            if (! is_array($recipient)) {
                continue;
            }

            $encoded = $recipient['edek_b64'] ?? null;

            if (! is_string($encoded) || $encoded === '') {
                continue;
            }

            $normalized = str_starts_with($encoded, 'b64:')
                ? substr($encoded, 4)
                : $encoded;

            $decoded = base64_decode($normalized, true);

            if ($decoded === false || $decoded === '') {
                continue;
            }

            try {
                $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }

            $ephemeral = $payload['from_ephemeral_public_key'] ?? null;

            if (is_string($ephemeral) && $ephemeral !== '') {
                return $ephemeral;
            }
        }

        return null;
    }

    protected function applyEnvelopeMetadataToResource(
        array $presented,
        mixed $recipients,
        ?string $fromEphemeralPublicKey
    ): array {
        $envelopeAttributesPath = 'data.relationships.envelope.data.attributes';

        $attributes = data_get($presented, $envelopeAttributesPath);

        if (! is_array($attributes)) {
            return $presented;
        }

        $normalizedRecipients = is_array($recipients) ? array_values($recipients) : $recipients;

        data_set($presented, $envelopeAttributesPath.'.recipients', $normalizedRecipients);

        if ($fromEphemeralPublicKey !== null) {
            data_set(
                $presented,
                $envelopeAttributesPath.'.from_ephemeral_public_key',
                $fromEphemeralPublicKey
            );
        }

        return $presented;
    }
}
