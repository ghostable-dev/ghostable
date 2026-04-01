<?php

namespace App\Account\Rules;

use App\Account\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRules
{
    public static function registrationRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => array_merge(self::emailRules(), self::createEmailRules()),
            'password' => self::confirmedPasswordRules(),
            'terms' => ['accepted', 'required'],
        ];
    }

    public static function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    public static function emailRules(): array
    {
        return ['required', 'string', 'email', 'max:255'];
    }

    public static function confirmedPasswordRules(): array
    {
        return array_merge(self::passwordRules(), [
            'confirmed',
        ]);
    }

    public static function passwordRules(): array
    {
        return [
            'required',
            'string',
            Password::default(12)->letters()->numbers()->symbols(),
        ];
    }

    public static function createEmailRules(): array
    {
        return [
            Rule::unique('users')
                ->where(fn ($query) => $query->whereNull('deleted_at')),
        ];
    }

    public static function updateEmailRules(User $user): array
    {
        return array_merge(self::emailRules(), [
            Rule::unique('users')
                ->where(fn ($query) => $query->whereNull('deleted_at'))
                ->ignore($user->id),
        ]);
    }
}
