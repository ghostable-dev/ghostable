<?php

namespace App\Auth\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke()
    {
        // If this is NOT a 2FA in-progress logout, wipe the session
        // if (! session()->has('login.id')) {
        //     
        // }

        Auth::guard('web')->logout();
        
        Session::invalidate();
        Session::regenerateToken();

        return redirect('/');
    }
}
