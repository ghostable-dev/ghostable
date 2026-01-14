<?php

namespace App\Core\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;

trait HandlesRecaptcha
{
    protected function recaptchaEnabled(): bool
    {
        return App::isProduction() && ! is_null($this->recaptchaSecret());
    }

    protected function recaptchaSecret(): ?string
    {
        return (string) config('services.recaptcha.secret');
    }

    protected function recaptchaKey(): ?string
    {
        return (string) config('services.recaptcha.key');
    }

    protected function verifyRecaptcha(Request $request, string $token, float $minScore = 0.5): bool
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $this->recaptchaSecret(),
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        if (! $response->ok()) {
            return false;
        }

        return (bool) $response->json('success') && $response->json('score') >= $minScore;
    }
}
