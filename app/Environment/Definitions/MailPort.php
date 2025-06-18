<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class MailPort extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'MAIL_PORT';
    }

    public function rule(): string
    {
        return 'required|integer|min:1|max:65535';
    }

    public function description(): ?string
    {
        return 'The port your mail server uses.';
    }

    public function suggestedValues(): array
    {
        return ['25', '465', '587'];
    }

    public function inputType(): ?string
    {
        return 'number';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Mail;
    }
}