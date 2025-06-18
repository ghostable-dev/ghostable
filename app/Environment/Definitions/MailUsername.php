<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class MailUsername extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'MAIL_USERNAME';
    }

    public function rule(): string
    {
        return 'nullable|string|max:255';
    }

    public function description(): ?string
    {
        return 'The username used to authenticate with your mail server.';
    }

    public function inputType(): ?string
    {
        return 'text';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Mail;
    }
}