<?php

namespace Tests\Http\Controllers\AuthController;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function rota(): string
    {
        return '/api/user';
    }

    #[Test, TestDox('Deve retornar o usuário autenticado no envelope data')]
    public function sucesso(): void
    {
        $user = $this->login();

        $this->getJson($this->rota())
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    #[Test, TestDox('Não deve retornar o usuário sem estar logado')]
    public function erroNaoLogado(): void
    {
        $this->getJson($this->rota())
            ->assertUnauthorized();
    }
}
