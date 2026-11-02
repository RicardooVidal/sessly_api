<?php

namespace Tests\Http\Controllers\AuthController;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    public function rota(): string
    {
        return route('logout');
    }

    #[Test, TestDox('Deve encerrar a sessão com sucesso')]
    public function sucesso(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson($this->rota())
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test, TestDox('Não deve encerrar a sessão sem estar logado')]
    public function erroNaoLogado(): void
    {
        $this->postJson($this->rota())
            ->assertUnauthorized();
    }
}
