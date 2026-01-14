<?php

namespace App\Core\Http\Controllers;

use App\Core\Enums\InquiryType;
use App\Core\Http\Controllers\Concerns\HandlesRecaptcha;
use App\Core\Models\Inquiry;
use Illuminate\Http\Request;

class SecurityIssueController extends Controller
{
    use HandlesRecaptcha;

    public function create()
    {
        return view(view: 'site.security-report', data: [
            'recaptchaEnabled' => $this->recaptchaEnabled(),
            'recaptchaKey' => $this->recaptchaKey(),
            'securityEmail' => (string) config('contact.security.email'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->getRules());

        if ($this->recaptchaEnabled() && ! $this->verifyRecaptcha($request, $validated['recaptcha_token'])) {
            return back()->withErrors([
                'recaptcha_token' => 'reCAPTCHA verification failed or suspicious behavior detected.',
            ])->withInput();
        }

        Inquiry::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'inquiry' => InquiryType::SECURITY,
            'message' => $validated['message'],
        ]);

        return back()->with('status', 'Thanks for the report. Our team will review it soon.');
    }

    protected function getRules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string'],
        ];

        if ($this->recaptchaEnabled()) {
            $rules['recaptcha_token'] = ['required', 'string'];
        }

        return $rules;
    }
}
