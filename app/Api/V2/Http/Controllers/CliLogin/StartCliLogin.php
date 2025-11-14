<?php

namespace App\Api\V2\Http\Controllers\CliLogin;

use App\Api\V2\Http\Controllers\CliSession\StartCliSession;

class StartCliLogin extends StartCliSession
{
    protected function pollPath(): string
    {
        return '/api/v2/cli/login/poll';
    }
}
