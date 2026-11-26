<?php

namespace Tests\Http;

use App\Http\Middleware\TelescopeAuth;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

class TelescopeAccessTest extends TestCase
{
    #[Test, TestDox('Deve salvar a sessão no banco sem preencher o UUID adicional')]
    public function salvaSessaoSemUuid(): void
    {
        $createTables = require database_path('migrations/0001_01_01_000000_create_users_table.php');
        $fixSessions = require database_path('migrations/2026_11_26_120000_make_session_uuid_nullable.php');
        $createTables->up();
        $fixSessions->up();

        try {
            $handler = new DatabaseSessionHandler(DB::connection(), 'sessions', 120);
            $handler->write('telescope-session-test', 'session-payload');

            $this->assertSame('session-payload', $handler->read('telescope-session-test'));
        } finally {
            $createTables->down();
        }
    }

    #[Test, TestDox('Deve negar acesso sem autenticação e sem redirecionar para o login da API')]
    public function erroNaoLogado(): void
    {
        $this->postJson('/api/telescope-access')
            ->assertForbidden()
            ->assertJson(['message' => 'Não autorizado']);
    }

    #[Test, TestDox('Deve negar acesso ao usuário autenticado sem permissão para o Telescope')]
    public function erroSemPermissao(): void
    {
        Gate::define('viewTelescope', fn (User $user) => false);
        $this->actingAs(new User, 'sanctum');

        $this->withToken('test-token')->postJson('/api/telescope-access')
            ->assertForbidden()
            ->assertJson(['message' => 'Não autorizado']);
    }

    #[Test, TestDox('Deve trocar o código de uso único por uma sessão para o usuário autorizado')]
    public function trocaCodigoPorSessao(): void
    {
        Gate::define('viewTelescope', fn (User $user) => true);
        $this->actingAs(new User, 'sanctum');

        $url = $this->withToken('test-token')->postJson('/api/telescope-access')
            ->assertOk()->json('url');

        $this->assertStringNotContainsString('test-token', $url);
        $this->get($url)->assertRedirect('/telescope')
            ->assertSessionHas('telescope_token', 'test-token');
        $this->get($url)->assertForbidden()->assertSeeText('Não autorizado');
    }

    #[Test, TestDox('Deve negar acesso quando o código temporário estiver expirado')]
    public function erroCodigoExpirado(): void
    {
        Gate::define('viewTelescope', fn (User $user) => true);
        $this->actingAs(new User, 'sanctum');
        $url = $this->withToken('test-token')->postJson('/api/telescope-access')->json('url');

        $this->travel(2)->minutes();

        $this->get($url)->assertForbidden()->assertSeeText('Não autorizado');
    }

    #[Test, TestDox('Deve negar acesso quando o Sanctum rejeitar o token da sessão')]
    public function erroTokenDaSessaoInvalido(): void
    {
        $request = Request::create('/telescope');
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('telescope_token', 'revoked-token');
        Auth::shouldReceive('guard')->with('sanctum')->once()->andReturnSelf();
        Auth::shouldReceive('user')->once()->andReturnNull();

        $response = (new TelescopeAuth)->handle($request, function () {
            $this->fail('An invalid token must not reach Telescope.');
        });

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Não autorizado', $response->getContent());
    }

    #[Test, TestDox('Deve encaminhar o token da sessão ao Sanctum e validar a permissão')]
    public function autenticaTokenDaSessao(): void
    {
        $request = Request::create('/telescope');
        $request->setLaravelSession(app('session.store'));
        $request->session()->put('telescope_token', 'session-token');
        Auth::shouldReceive('guard')->with('sanctum')->once()->andReturnSelf();
        Auth::shouldReceive('user')->once()->andReturn(new User);
        Auth::shouldReceive('shouldUse')->with('sanctum')->once();
        Gate::define('viewTelescope', fn (User $user) => true);

        $response = (new TelescopeAuth)->handle($request, function (Request $request) {
            $this->assertSame('session-token', $request->bearerToken());

            return response('Authorized');
        });

        $this->assertSame(200, $response->getStatusCode());
    }
}
