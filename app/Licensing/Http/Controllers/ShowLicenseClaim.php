<?php

namespace App\Licensing\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Licensing\Models\License;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ShowLicenseClaim extends Controller
{
    public function __invoke(Request $request, License $license): View
    {
        if ($request->user() === null || $license->purchaser_user_id !== $request->user()->getKey()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return view('site.license-claim', [
            'license' => $license->loadMissing('organization'),
            'claimUrl' => $request->fullUrl(),
        ]);
    }
}
