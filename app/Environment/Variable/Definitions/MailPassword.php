<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class MailPassword extends VariableDefinition
{
    public function key(): string
    {
        return 'MAIL_PASSWORD';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The password used to authenticate with your mail server.';
    }
    // @codeCoverageIgnoreEnd

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Mail;
    }
    // @codeCoverageIgnoreEnd
}
