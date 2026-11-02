<?php

namespace Tests\Http\Controllers\AuthController;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class LoginTest extends TestCase
{
    public function rota(): string
    {
        return route('login');
    }

    #[Test, TestDox('Deve logar com sucesso')]
    public function deveLogarComSucesso(): void
    {
        $user = $this->login();

        $this->postJson($this->rota(), [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token']]);
    }

    #[Test, TestDox('Não deve logar com dados inválidos')]
    public function naoDeveLogarDadosInvalidos(): void
    {
        $this->postJson($this->rota(), [
            'email' => $this->faker->word,
            'password' => $this->faker->boolean,
        ])
            ->assertUnprocessable();
    }

    #[Test, TestDox('Não deve logar com credenciais inválidas')]
    public function naoDeveLogarComCredenciaisInvalidas(): void
    {
        $this->postJson($this->rota(), [
            'email' => $this->faker->email,
            'password' => 'password',
        ])
            ->assertUnauthorized();
    }
}
