<?php

namespace App\Http\Controllers\Vip;

use App\Http\Controllers\Controller;

class LogoutController extends Controller
{
    public function __invoke()
    {
        auth('vip_code')->logout();
        session()->forget(['vip_code_id', 'vip_code', 'concierge_id']);

        return redirect('/');
    }
}
