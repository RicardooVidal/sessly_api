<?php

namespace Tests\Services;

use App\Models\GoogleAccount;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Exception as GoogleException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;

class GoogleCalendarServiceTest extends TestCase
{
    #[Test, TestDox('Deve configurar o cliente com acesso offline ao calendário')]
    public function sucessoConfiguracaoCliente(): void
    {
        config(['services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret', 'services.google.redirect' => 'https://example.test/callback']);
        $client = (new GoogleCalendarService)->client();
        parse_str(parse_url($client->createAuthUrl(), PHP_URL_QUERY), $query);
        $this->assertSame('test-client', $query['client_id']);
        $this->assertSame('https://example.test/callback', $query['redirect_uri']);
        $this->assertSame(Calendar::CALENDAR, $query['scope']);
        $this->assertSame('offline', $query['access_type']);
        $this->assertSame('consent', $query['prompt']);
    }

    #[Test, TestDox('Deve descriptografar o refresh token para autenticar o cliente')]
    public function sucessoAutenticacao(): void
    {
        $account = new GoogleAccount(['refresh_token' => encrypt('refresh-test')]);
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('fetchAccessTokenWithRefreshToken')->once()->with('refresh-test')->andReturn(['access_token' => 'access-test']);
        $service = Mockery::mock(GoogleCalendarService::class)->makePartial();
        $service->shouldReceive('client')->once()->andReturn($client);
        $this->assertSame($client, $service->authenticatedClient($account));
    }

    #[DataProvider('errosRenovacaoTokenDataProvider')]
    #[Test, TestDox('Deve informar o erro ao renovar o token do Google')]
    public function erroRenovacaoToken(array $response, string $message): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('fetchAccessTokenWithRefreshToken')->once()->with('refresh-test')->andReturn($response);
        $service = Mockery::mock(GoogleCalendarService::class)->makePartial();
        $service->shouldReceive('client')->once()->andReturn($client);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($message);
        $service->authenticatedClient(new GoogleAccount(['refresh_token' => encrypt('refresh-test')]));
    }

    public static function errosRenovacaoTokenDataProvider(): array
    {
        return [
            'erro com descrição' => [['error' => 'invalid_grant', 'error_description' => 'Token revogado'], 'Token revogado'],
            'erro sem descrição' => [['error' => 'invalid_grant'], 'Não foi possível renovar o token do Google.'],
        ];
    }

    private function criarServicoComResposta(Response $response, array &$history): GoogleCalendarService
    {
        $stack = HandlerStack::create(new MockHandler([$response]));
        $stack->push(Middleware::history($history));
        $client = new Client;
        $client->setHttpClient(new HttpClient(['handler' => $stack]));
        $client->setAccessToken(['access_token' => 'test', 'created' => time(), 'expires_in' => 3600]);
        $service = Mockery::mock(GoogleCalendarService::class)->makePartial();
        $service->shouldReceive('authenticatedClient')->once()->with(Mockery::type(GoogleAccount::class))->andReturn($client);

        return $service;
    }

    #[DataProvider('operacoesReuniaoDataProvider')]
    #[Test, TestDox('Deve enviar os dados da reunião ao calendário do Google')]
    public function sucessoReuniao(string $operation, string $method, string $path): void
    {
        $history = [];
        $service = $this->criarServicoComResposta(new Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => 'event-test', 'hangoutLink' => 'https://meet.google.com/test'])), $history);
        $start = Carbon::parse('2026-10-10 09:00:00', 'America/Sao_Paulo');
        $end = $start->copy()->addMinutes(50);
        $args = [new GoogleAccount, 'Consulta de João', $start, $end];
        if ($operation === 'updateMeeting') {
            array_unshift($args, 'event-test');
        }
        $event = $service->{$operation}(...$args);
        $this->assertSame('event-test', $event->getId());
        $this->assertSame('https://meet.google.com/test', $event->getHangoutLink());
        $this->assertCount(1, $history);
        $request = $history[0]['request'];
        $this->assertSame($method, $request->getMethod());
        $this->assertSame($path, $request->getUri()->getPath());
        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame('1', $query['conferenceDataVersion']);
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame('Consulta de João', $body['summary']);
        $this->assertSame($start->format(DATE_RFC3339), $body['start']['dateTime']);
        $this->assertSame($end->format(DATE_RFC3339), $body['end']['dateTime']);
        $this->assertSame('America/Sao_Paulo', $body['start']['timeZone']);
        $this->assertSame('America/Sao_Paulo', $body['end']['timeZone']);
        $this->assertSame('hangoutsMeet', $body['conferenceData']['createRequest']['conferenceSolutionKey']['type']);
        $this->assertTrue(Str::isUuid($body['conferenceData']['createRequest']['requestId']));
    }

    public static function operacoesReuniaoDataProvider(): array
    {
        return [
            'criação de reunião' => ['createMeeting', 'POST', '/calendar/v3/calendars/primary/events'],
            'atualização de reunião' => ['updateMeeting', 'PATCH', '/calendar/v3/calendars/primary/events/event-test'],
        ];
    }

    #[Test, TestDox('Deve excluir o evento informado do calendário')]
    public function sucessoExclusao(): void
    {
        $history = [];
        $service = $this->criarServicoComResposta(new Response(204), $history);
        $service->deleteMeeting('event-test', new GoogleAccount);
        $this->assertCount(1, $history);
        $this->assertSame('DELETE', $history[0]['request']->getMethod());
        $this->assertSame('/calendar/v3/calendars/primary/events/event-test', $history[0]['request']->getUri()->getPath());
    }

    #[Test, TestDox('Deve propagar o erro retornado pela API do Google')]
    public function erroApiGoogle(): void
    {
        $history = [];
        $service = $this->criarServicoComResposta(new Response(403, ['Content-Type' => 'application/json'], '{"error":{"code":403,"message":"Forbidden"}}'), $history);
        $this->expectException(GoogleException::class);
        $this->expectExceptionCode(403);
        $service->deleteMeeting('event-test', new GoogleAccount);
    }
}
