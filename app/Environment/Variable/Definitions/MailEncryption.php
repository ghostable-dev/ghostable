<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class MailEncryption extends VariableDefinition
{
    public function key(): string
    {
        return 'MAIL_ENCRYPTION';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The encryption protocol to use when sending mail.';
    }
    // @codeCoverageIgnoreEnd

    public function suggestedValues(): array
    {
        return ['tls', 'ssl', 'null'];
    }

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Mail;
    }
    // @codeCoverageIgnoreEnd

    public function ruleProviders(): array
    {
        return [];
    }
}
