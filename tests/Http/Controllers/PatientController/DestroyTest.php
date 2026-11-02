<?php

namespace Tests\Http\Controllers\PatientController;

use App\Domains\Patient\Entities\Patient;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    public function rota(string $uuid): string
    {
        return route('patient.destroy', ['patient' => $uuid]);
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $uuid = $this->faker->uuid;

        $this->assertEquals(
            $this->rota($uuid),
            URL::action([PatientController::class, 'destroy'], ['patient' => $uuid])
        );
    }

    #[
        Test,
        TestDox('Deve atualizar um paciente com sucesso'),
    ]
    public function sucesso(): void
    {
        $patient = $this->criarPaciente();

        $this->login();

        $this->deleteJson($this->rota($patient->uuid))
            ->assertNoContent();
    }

    #[
        Test,
        TestDox('Deve retornar 404 ao tentar deletar um paciente inexistente'),
    ]
    public function erroPacienteInexistente(): void
    {
        $this->login();

        $this
            ->deleteJson($this->rota($this->faker->uuid))
            ->assertNotFound();
    }

    #[
        Test,
        TestDox('Deve retornar erro ao tentar deletar um paciente sem estar logado'),
    ]
    public function erroNaoLogado(): void
    {
        $patient = $this->criarPaciente();

        $this->deleteJson($this->rota($patient->uuid))
            ->assertUnauthorized();
    }

    private function criarPaciente(): Patient
    {
        return Patient::factory()->create();
    }
}
