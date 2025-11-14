<?php

namespace App\Api\V2\Http\Controllers\CliRegister;

use App\Api\V2\Http\Controllers\CliSession\StartCliSession;
use App\Auth\Models\CliLoginSession;

class StartCliRegister extends StartCliSession
{
    protected function pollPath(): string
    {
        return '/api/v2/cli/register/poll';
    }

    protected function approvalUrlKey(): string
    {
        return 'register_url';
    }

    protected function approvalUrl(CliLoginSession $session): string
    {
        return route('register', ['ticket' => $session->id]);
    }
}
