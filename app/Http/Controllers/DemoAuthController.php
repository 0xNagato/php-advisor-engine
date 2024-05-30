<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class DemoAuthController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function auth(int|string $user_id, Request $request): RedirectResponse
    {
        abort_if($request->cookie('user_id') !== $user_id, 401);

        info('User ID: '.$user_id.' logged in as demo concierge.');
        $user = User::query()->findOrFail(5);
        Auth::login($user);

        return redirect()->route('filament.admin.pages.concierge.reservation-hub');
    }

    public function redirect()
    {
        $url = config('app.url');
        $host = parse_url((string) $url, PHP_URL_HOST);
        $parts = explode('.', $host);
        $domain = '.'.implode('.', array_slice($parts, -2));

        $cookie = Cookie::make('user_id', auth()->id(), 60, null, $domain);

        return redirect($this->generateDemoLoginLink())->withCookie($cookie);
    }

    public function generateDemoLoginLink(): string
    {
        $url = route('demo.auth', ['user_id' => auth()->id()], absolute: false);

        return "https://demo.primavip.co$url";
    }
}
