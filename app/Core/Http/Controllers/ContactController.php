<?php

namespace App\Core\Http\Controllers;

use App\Core\Enums\InquiryType;
use App\Core\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Enum;

class ContactController extends Controller
{
    public function create()
    {
        return view(view: 'site.contact', data: [
            'recaptchaEnabled' => $this->recaptchaEnabled(),
            'recaptchaKey' => $this->recaptchaKey(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules());

        if ($this->recaptchaEnabled()) {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $this->recaptchaSecret(),
                'response' => $validated['recaptcha_token'],
                'remoteip' => $request->ip(),
            ]);

            if (! $response->json('success') || $response->json('score') < 0.5) {
                return back()->withErrors([
                    'recaptcha_token' => 'reCAPTCHA verification failed or suspicious behavior detected.',
                ])->withInput();
            }
        }

        Inquiry::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'inquiry' => $validated['inquiry'],
            'message' => $validated['message'],
        ]);

        return back()->with('status', 'Thanks for reaching out! We\'ll be in touch.');
    }

    protected function getRules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'inquiry' => ['required', new Enum(InquiryType::class)],
            'message' => ['required', 'string'],
        ];

        if ($this->recaptchaEnabled()) {
            $rules['recaptcha_token'] = ['required', 'string'];
        }

        return $rules;
    }

    private function recaptchaEnabled(): bool
    {
        return App::isProduction() && ! is_null($this->recaptchaSecret());
    }

    private function recaptchaSecret(): ?string
    {
        return (string) config('services.recaptcha.secret');
    }

    private function recaptchaKey(): ?string
    {
        return (string) config('services.recaptcha.key');
    }
}
