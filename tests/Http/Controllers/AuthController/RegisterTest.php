<?php

namespace Http\Controllers\AuthController;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    public function rota(): string
    {
        return route('register');
    }

    #[Test, TestDox('Deve registrar um usuário com sucesso')]
    public function deveRegistrarComSucesso(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->withCompany()
            ->make();

        $this->postJson($this->rota(), [
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure(['data' => ['token']]);
    }

    #[Test, TestDox('Não deve registrar com a falta da confirmação de senha')]
    public function naoDeveRegistrarSemConfirmacaoSenha(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->withCompany()
            ->make();

        $this->postJson($this->rota(), [
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'password' => 'password',
        ])
            ->assertUnprocessable();
    }

    #[Test, TestDox('Não deve registrar usuário com dados inválidos')]
    public function naoDeveRegistrarDadosInvalidos(): void
    {
        /** @var User $user */
        $user = User::factory()
            ->withCompany()
            ->make();

        $this->postJson($this->rota(), [
            'name' => $user->name,
            'email' => $user->email,
            'company_id' => $user->company_id,
            'password' => 'password',
        ])
            ->assertUnprocessable();
    }
}
