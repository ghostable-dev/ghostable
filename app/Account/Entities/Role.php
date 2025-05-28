<?php

namespace App\Account\Entities;

use App\Account\Providers\ACLServiceProvider;
use JsonSerializable;

class Role implements JsonSerializable
{
    public string $description = '';

    /**
     * @param  Permission[]  $permissions
     */
    public function __construct(
        public string $key,
        public string $name,
        public array $permissions
    ) {}

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function isCustom(): bool
    {
        return $this->key === ACLServiceProvider::ROLE_CUSTOM;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'key' => $this->key,
            'name' => __($this->name),
            'description' => __($this->description),
            'permissions' => collect($this->permissions)->map(fn ($p) => $p->value)->all(),
        ];
    }
}
