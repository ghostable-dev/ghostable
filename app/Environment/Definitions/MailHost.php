<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class MailHost extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'MAIL_HOST';
    }

    public function rule(): string
    {
        return 'required|string|max:255';
    }

    public function description(): ?string
    {
        return 'The hostname of your mail server.';
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