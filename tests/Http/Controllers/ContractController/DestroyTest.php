<?php

namespace Http\Controllers\ContractController;

use App\Domains\Contract\Entities\Contract;
use App\Http\Controllers\ContractController;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    public function rota(string $uuid): string
    {
        return route('contract.destroy', ['contract' => $uuid]);
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $uuid = $this->faker->uuid;

        $this->assertEquals(
            $this->rota($uuid),
            URL::action([ContractController::class, 'destroy'], ['contract' => $uuid])
        );
    }

    #[
        Test,
        TestDox('Deve deletar um contrato com sucesso'),
    ]
    public function sucesso(): void
    {
        $contrato = $this->criarContrato();

        $this->login();

        $this->deleteJson($this->rota($contrato->uuid))
            ->assertNoContent();
    }

    #[
        Test,
        TestDox('Deve retornar 404 ao tentar deletar um contrato inexistente'),
    ]
    public function erroContratoInexistente(): void
    {
        $this->login();

        $this
            ->deleteJson($this->rota($this->faker->uuid))
            ->assertNotFound();
    }

    #[
        Test,
        TestDox('Deve retornar erro ao tentar deletar um contrato sem estar logado'),
    ]
    public function erroNaoLogado(): void
    {
        $contrato = $this->criarContrato();

        $this->deleteJson($this->rota($contrato->uuid))
            ->assertUnauthorized();
    }

    private function criarContrato(): contract
    {
        return Contract::factory()
            ->withPatient()
            ->create();
    }
}
