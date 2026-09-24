<?php

namespace App\Services;

use App\Models\GoogleAccount;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Exception;
use Illuminate\Support\Str;

class GoogleCalendarService
{
    public function client(): Client
    {
        $client = new Client();

        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        $client->setScopes([
            Calendar::CALENDAR,
        ]);

        // Fundamental para receber refresh_token
        $client->setAccessType('offline');

        $client->setPrompt('consent');

        return $client;
    }

    /**
     * @throws Exception
     */
    public function createMeeting(
        GoogleAccount $googleAccount,
        string $name,
        Carbon $start,
        Carbon $end
    ): Event {
        $client = $this->authenticatedClient($googleAccount);

        $calendar = new Calendar($client);

        $event = new Event([
            'summary' => $name,

            'start' => [
                'dateTime' => $start->format(DATE_RFC3339),
                'timeZone' => 'America/Sao_Paulo',
            ],

            'end' => [
                'dateTime' => $end->format(DATE_RFC3339),
                'timeZone' => 'America/Sao_Paulo',
            ],

            'conferenceData' => [
                'createRequest' => [
                    'requestId' => (string) Str::uuid(),

                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ]);

        return $calendar->events->insert(
            calendarId: 'primary',
            postBody: $event,
            optParams: [
                'conferenceDataVersion' => 1,
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function updateMeeting(
        string $eventId,
        GoogleAccount $googleAccount,
        string $name,
        Carbon $start,
        Carbon $end
    ): Event
    {
        $client = $this->authenticatedClient($googleAccount);

        $calendar = new Calendar($client);

        $event = new Event([
            'summary' => $name,

            'start' => [
                'dateTime' => $start->format(DATE_RFC3339),
                'timeZone' => 'America/Sao_Paulo',
            ],

            'end' => [
                'dateTime' => $end->format(DATE_RFC3339),
                'timeZone' => 'America/Sao_Paulo',
            ],

            'conferenceData' => [
                'createRequest' => [
                    'requestId' => (string) Str::uuid(),

                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                ],
            ],
        ]);

        return $calendar->events->patch(
            calendarId: 'primary',
            eventId: $eventId,
            postBody: $event,
            optParams: [
                'conferenceDataVersion' => 1,
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function deleteMeeting(
        string $eventId,
        GoogleAccount $googleAccount,
    ): void
    {
        $client = $this->authenticatedClient($googleAccount);

        $calendar = new Calendar($client);

        $calendar->events->delete(
            calendarId: 'primary',
            eventId: $eventId,
        );
    }

    public function authenticatedClient(GoogleAccount $googleAccount): Client
    {
        $client = $this->client();

        $refreshToken = decrypt(
            $googleAccount->refresh_token
        );

        $token = $client->fetchAccessTokenWithRefreshToken(
            $refreshToken
        );

        if (isset($token['error'])) {
            throw new \RuntimeException(
                $token['error_description']
                ?? 'Não foi possível renovar o token do Google.'
            );
        }

        return $client;
    }
}
