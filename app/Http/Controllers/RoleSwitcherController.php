<?php

namespace App\Http\Controllers;

use App\Models\RoleProfile;
use Illuminate\Http\RedirectResponse;

class RoleSwitcherController extends Controller
{
    public function switch(RoleProfile $profile): RedirectResponse
    {
        if ($profile->user_id !== auth()->id()) {
            return redirect()->back();
        }

        if ($profile->is_active) {
            return redirect()->back();
        }

        auth()->user()->switchProfile($profile);

        return redirect(config('app.platform_url'));
    }
}
