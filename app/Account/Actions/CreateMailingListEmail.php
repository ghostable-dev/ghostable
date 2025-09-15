<?php

namespace App\Account\Actions;

use App\Account\Enums\MailingListEmailSource;
use App\Account\Models\MailingListEmail;

class CreateMailingListEmail
{
    public static function handle(
        string $email,
        MailingListEmailSource $source,
        ?array $sourcePayload = null
    ): MailingListEmail {
        if ($existing = MailingListEmail::where('email', $email)->withTrashed()->first()) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            return $existing;
        }

        return MailingListEmail::create([
            'email' => $email,
            'source' => $source,
            'sourcePayload' => $sourcePayload,
        ]);
    }
}
