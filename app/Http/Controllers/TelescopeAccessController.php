<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TelescopeAccessController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::guard('sanctum')->user();

        if (! $user || ! $request->bearerToken() || ! Gate::forUser($user)->allows('viewTelescope')) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $ticket = Str::random(64);
        Cache::put('telescope-access:'.$ticket, Crypt::encryptString($request->bearerToken()), now()->addMinute());

        return response()->json([
            'url' => route('telescope.access', ['ticket' => $ticket]),
        ])->header('Cache-Control', 'no-store');
    }

    public function show(Request $request, string $ticket)
    {
        $token = Cache::pull('telescope-access:'.$ticket);

        if (! $token) {
            return response('Não autorizado', 403);
        }

        $request->session()->regenerate();
        $request->session()->put('telescope_token', Crypt::decryptString($token));

        return redirect('/'.trim(config('telescope.path'), '/'))
            ->header('Referrer-Policy', 'no-referrer')
            ->header('Cache-Control', 'no-store');
    }
}
