<?php

namespace App\Account\Actions;

use App\Account\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;

class RegisterUser
{
    public function handle(array $data): User
    {
        $data['email'] = strtolower($data['email']);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        event(new Registered($user));

        return $user;
    }
}
