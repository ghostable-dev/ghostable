<?php

namespace App\Environment\Deployment;

use App\Environment\Deployment\Contracts\DeploymentProviderHandler;
use InvalidArgumentException;

class DeploymentProviderResolver
{
    /** @var array<string, DeploymentProviderHandler> */
    private array $handlers;

    /**
     * @param  array<string, DeploymentProviderHandler>  $handlers
     */
    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }

    public function resolve(string $provider): DeploymentProviderHandler
    {
        return $this->handlers[$provider]
            ?? throw new InvalidArgumentException("No deployment handler for provider: {$provider}");
    }
}
