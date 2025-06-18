<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class MailEncryption extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'MAIL_ENCRYPTION';
    }

    public function rule(): string
    {
        return 'nullable|in:tls,ssl,null';
    }

    public function description(): ?string
    {
        return 'The encryption protocol to use when sending mail.';
    }

    public function suggestedValues(): array
    {
        return ['tls', 'ssl', 'null'];
    }

    public function inputType(): ?string
    {
        return 'select';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Mail;
    }
}