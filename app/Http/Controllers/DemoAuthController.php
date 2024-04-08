<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoAuthController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(int $user_id, Request $request)
    {
        info('User ID: ' . $user_id . ' logged in as demo concierge.');
        $user = User::findOrFail(5);
        Auth::login($user);
        
        return redirect()->route('filament.admin.pages.concierge.reservation-hub');
    }
}
