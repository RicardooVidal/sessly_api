<?php

namespace Http\Controllers\ContractController;

use App\Domains\Contract\Entities\Contract;
use App\Http\Controllers\ContractController;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ShowTest extends TestCase
{
    public function rota(string $uuid): string
    {
        return route('contract.show', ['contract' => $uuid]);
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $uuid = $this->faker->uuid;

        $this->assertEquals(
            $this->rota($uuid),
            URL::action([ContractController::class, 'show'], ['contract' => $uuid])
        );
    }

    #[
        Test,
        TestDox('Deve exibir um contrato com sucesso'),
    ]
    public function sucesso(): void
    {
        $contract = $this->criarContrato();
        $this->login();

        $this
            ->getJson($this->rota($contract->uuid))
            ->assertOk()
            ->assertJsonFragment([
                'data' => [
                    'uuid' => $contract->uuid,
                    'patient' => [
                        'uuid' => $contract->patient->uuid,
                        'name' => $contract->patient->name,
                        'phone' => null,
                        'gender' => null,
                        'email' => null,
                        'type' => null,
                        'time' => null,
                        'document' => null,
                        'active' => null,
                        'create_appointments' => null,
                        'day' => null,
                        'notify_appointment' => null,
                        'notes' => null,
                        'value' => null,
                    ],
                    'description' => $contract->description,
                    'valid_until' => $contract->valid_until->toW3cString(),
                    'document_url' => $contract->document_url,
                    'created_at' => $contract->created_at->toW3cString(),
                    'updated_at' => $contract->updated_at->toW3cString(),
                    'file' => null,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'uuid',
                    'patient',
                    'description',
                    'valid_until',
                    'document_url',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    #[Test, TestDox('Deve retornar 404 para contrato inexistente')]
    public function erroContratoInexistente(): void
    {
        $this->login();

        $this
            ->getJson($this->rota($this->faker->uuid))
            ->assertNotFound();
    }

    #[Test, TestDox('Deve retornar erro ao tentar exibir um contrato sem estar logado')]
    public function erroNaoLogado(): void
    {
        $contract = $this->criarContrato();

        $this
            ->getJson($this->rota($contract->uuid))
            ->assertUnauthorized();
    }

    private function criarcontrato(): contract
    {
        return Contract::factory()
            ->withpatient()
            ->create();
    }
}
