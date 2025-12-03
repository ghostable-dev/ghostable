<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class MailHost extends VariableDefinition
{
    public function key(): string
    {
        return 'MAIL_HOST';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The hostname of your mail server.';
    }
    // @codeCoverageIgnoreEnd

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
