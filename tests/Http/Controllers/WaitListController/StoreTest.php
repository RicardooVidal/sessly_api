<?php

namespace Http\Controllers\WaitListController;

use App\Domains\Patient\Entities\Patient;
use App\Domains\Patient\Entities\WaitList;
use App\Http\Controllers\WaitListController;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class StoreTest extends TestCase
{
    public function rota(): string
    {
        return route('wait-list.store');
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $this->assertEquals(
            $this->rota(),
            URL::action([WaitListController::class, 'store'])
        );
    }

    #[
        Test,
        TestDox('Deve cadastrar um paciente na lista de espera com sucesso'),
    ]
    public function sucesso(): void
    {
        $paciente = $this->criarPaciente();

        $this->login();

        $this
            ->postJson($this->rota(), ['patient_uuid' => $paciente->uuid])
            ->assertCreated()
            ->assertJsonFragment([
                'patient_uuid' => $paciente->uuid,
            ]);

        $this->assertDatabaseHas(WaitList::class, [
            'patient_id' => $paciente->id,
        ]);
    }

    #[Test, TestDox('Deve retornar erro ao tentar cadastrar paciente inexistente na lista de espera')]
    public function erroPacienteInexistente(): void
    {
        $this->login();

        $this
            ->postJson($this->rota(), ['patient_uuid' => $this->faker->uuid])
            ->assertNotFound();
    }

    #[Test, TestDox('Deve retornar erro enviar um uuid de paciente inválido')]
    public function erroParametroInvalido(): void
    {
        $this->login();

        $this
            ->postJson($this->rota(), ['patient_uuid' => 'valor-invalido'])
            ->assertUnprocessable();
    }

    #[Test, TestDox('Deve retornar erro ao tentar cadastrar um paciente na lista de espera sem estar logado')]
    public function erroNaoLogado(): void
    {
        $paciente = $this->criarPaciente();

        $this
            ->postJson($this->rota(), ['patient_uuid' => $paciente->uuid])
            ->assertUnauthorized();
    }

    private function criarPaciente(): Patient
    {
        return Patient::factory()->create();
    }
}
