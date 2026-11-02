<?php

namespace App\Http\Controllers;

use App\Models\GoogleAccount;
use App\Models\GoogleOAuthState;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GoogleCalendarController extends Controller
{
    public function connect(
        Request $request,
        GoogleCalendarService $google
    ): JsonResponse {
        $state = Str::random(64);

        GoogleOAuthState::query()->create([
            'user_id' => $request->user()->id,
            'state' => $state,
            'expires_at' => now()->addMinutes(10),
        ]);

        $client = $google->client();

        $client->setState($state);

        return response()->json([
            'data' => [
                'url' => $client->createAuthUrl(),
            ],
        ]);
    }

    public function redirect(GoogleCalendarService $google): RedirectResponse
    {
        $client = $google->client();

        return redirect()->away(
            $client->createAuthUrl()
        );
    }

    /**
     * @throws BindingResolutionException
     */
    public function callback(
        Request $request,
        GoogleCalendarService $google
    ): mixed {
        $client = $google->client();

        $token = $client->fetchAccessTokenWithAuthCode(
            $request->get('code')
        );

        if (isset($token['error'])) {
            abort(400, $token['error_description'] ?? $token['error']);
        }

        /** @var GoogleOAuthState $state */
        $state = GoogleOAuthState::query()->where('state', $request->get('state'))->first();

        $user = $state->user;

        if (! empty($token['refresh_token'])) {
            GoogleAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                ],
                [
                    'refresh_token' => encrypt(
                        $token['refresh_token']
                    ),
                ]
            );
        }

        return response()->make('
            <!DOCTYPE html>
            <html>
            <head>
                <title>Google conectado</title>
            </head>
            <body>
            <script>
                if (window.opener) {
                    window.opener.postMessage(
                        {
                            type: "GOOGLE_CONNECTED"
                        },
                        "*"
                    );
                }

                window.close();
            </script>
        ');
    }

    public function testMeeting(
        GoogleCalendarService $google
    ) {
        $token = session('google_token');
        abort_unless($token, 401, 'Google não conectado');

        $start = now()->addDay()->setTime(14, 0);
        $end = now()->addDay()->setTime(15, 0);

        $event = $google->createMeeting(
            $token,
            $start,
            $end
        );

        return response()->json([
            'data' => [
                'google_event_id' => $event->getId(),
                'calendar_url' => $event->getHtmlLink(),
                'meet_url' => $event->getHangoutLink(),
                'conference_data' => $event->getConferenceData(),
            ],
        ]);
    }

    public function test(GoogleCalendarService $google)
    {
        $googleAccount = GoogleAccount::where(
            'user_id',
            auth()->id()
        )->firstOrFail();

        $event = $google->createMeeting(
            $googleAccount,
            now()->addDay()->setTime(14, 0),
            now()->addDay()->setTime(15, 0),
        );

        return response()->json([
            'data' => [
                'event_id' => $event->getId(),
                'meet_url' => $event->getHangoutLink(),
            ],
        ]);
    }
}
