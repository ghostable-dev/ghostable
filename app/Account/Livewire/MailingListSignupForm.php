<?php

namespace App\Account\Livewire;

use App\Account\Actions\CreateMailingListEmail;
use App\Account\Enums\MailingListEmailSource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class MailingListSignupForm extends Component
{
    public string $email = '';

    public bool $submitted = false;

    public bool $tooManyRequests = false;

    public function save(): void
    {
        $executed = RateLimiter::attempt(
            key: 'mailing_list_signup:'.request()->ip(),
            maxAttempts: 5,
            callback: function () {
                $data = Validator::make(
                    data: ['email' => $this->email],
                    rules: [
                        'email' => ['required', 'string', 'email', 'max:255'],
                    ])->validate();
                CreateMailingListEmail::handle(
                    email: $data['email'],
                    source: MailingListEmailSource::BLOG
                );
                $this->submitted = true;
            }
        );

        if (! $executed) {
            $this->tooManyRequests = true;
        }
    }

    public function render()
    {
        return view('account.mailing-list-signup-form');
    }
}
