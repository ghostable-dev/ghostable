<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class MailPort extends VariableDefinition
{
    public function key(): string
    {
        return 'MAIL_PORT';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The port your mail server uses.';
    }
    // @codeCoverageIgnoreEnd

    public function suggestedValues(): array
    {
        return ['25', '465', '587'];
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
