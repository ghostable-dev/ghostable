<?php

namespace App\Core\Http\Controllers;

use App\Core\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Enum;
use App\Core\Enums\InquiryType;

class ContactController extends Controller
{
    public function create()
    {
        return view('core.contact');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'inquiry' => ['required', new Enum(InquiryType::class)],
            'message' => ['required', 'string'],
            'g-recaptcha-response' => ['required'],
        ]);

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret'),
            'response' => $validated['g-recaptcha-response'],
            'remoteip' => $request->ip(),
        ]);

        if (! $response->json('success')) {
            return back()->withErrors([
                'g-recaptcha-response' => 'reCAPTCHA verification failed.',
            ])->withInput();
        }

        Inquiry::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'inquiry' => $validated['inquiry'],
            'message' => $validated['message'],
        ]);

        return back()->with('status', 'Thanks for reaching out! We\'ll be in touch.');
    }
}
