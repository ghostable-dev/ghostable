<?php

namespace App\Account\Actions;

use App\Account\Models\User;
use Illuminate\Auth\Events\Registered;

class RegisterUser
{
    public function handle(array $data): User
    {
        $data['email'] = strtolower($data['email']);

        $user = User::create($data);

        event(new Registered($user));

        return $user;
    }
}
