<?php

namespace App\Auth\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuids;

    protected $fillable = [
        'name',
        'token',
        'token_suffix',
        'abilities',
        'expires_at',
    ];
}
