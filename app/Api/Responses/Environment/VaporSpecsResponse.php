<?php

declare(strict_types=1);

namespace App\Api\Responses\Environment;

use App\Environment\Entities\ResolvedVariableData;
use App\Environment\Variable\Enums\DeliveryMode;
use App\Environment\Variable\Models\EnvironmentVariable;
use JsonSerializable;

final class VaporSpecsResponse implements JsonSerializable
{
    public string $provider = 'vapor';

    /** @var array<string, mixed> */
    public array $provider_params = [];

    /**
     * @var array<string, mixed>
     */
    public array $variables = [
        DeliveryMode::STANDARD->name => [],
        DeliveryMode::SECRET->name => [],
        DeliveryMode::ENCRYPTED->name => null,
    ];

    /** @var array<string, int> */
    public array $counts = [];

    /**
     * @param  iterable<int, EnvironmentVariable|ResolvedVariableData>  $variables
     */
    public static function build(iterable $variables, array $providerParams, callable $encryptor): self
    {
        $response = new self;
        $response->provider_params = $providerParams;

        $standard = [];
        $secret = [];
        $encrypted = [];

        foreach ($variables as $item) {
            $variable = $item instanceof ResolvedVariableData ? $item->variable : $item;

            if (! $variable instanceof EnvironmentVariable) {
                continue;
            }

            $value = $variable->value ?? '';
            $entry = [
                'key' => $variable->key,
                'value' => $variable->value,
            ];

            match ($variable->delivery_mode) {
                DeliveryMode::STANDARD => $standard[] = $entry,
                DeliveryMode::SECRET => $secret[] = $entry,
                DeliveryMode::ENCRYPTED => $encrypted[$variable->key] = $value,
            };
        }

        $response->variables[DeliveryMode::STANDARD->name] = $standard;
        $response->variables[DeliveryMode::SECRET->name] = $secret;
        $response->variables[DeliveryMode::ENCRYPTED->name] = $encrypted
            ? $encryptor($encrypted)
            : null;

        $response->counts = [
            DeliveryMode::STANDARD->name => count($standard),
            DeliveryMode::SECRET->name => count($secret),
            DeliveryMode::ENCRYPTED->name => count($encrypted),
            'TOTAL' => count($standard) + count($secret) + count($encrypted),
        ];

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'provider_params' => $this->provider_params,
            'variables' => $this->variables,
            'counts' => $this->counts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
