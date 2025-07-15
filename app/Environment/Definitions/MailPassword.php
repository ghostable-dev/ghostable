<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class MailPassword extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'MAIL_PASSWORD';
    }

    public function description(): ?string
    {
        return 'The password used to authenticate with your mail server.';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Mail;
    }
}
