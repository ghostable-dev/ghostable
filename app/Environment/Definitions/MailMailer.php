<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class MailMailer extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'MAIL_MAILER';
    }

    public function rule(): string
    {
        return 'required|in:smtp,sendmail,mailgun,ses,postmark,log,array';
    }

    public function description(): ?string
    {
        return 'The default mailer your application will use.';
    }

    public function suggestedValues(): array
    {
        return ['smtp', 'sendmail', 'mailgun', 'ses', 'postmark', 'log', 'array'];
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