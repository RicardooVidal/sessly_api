<?php

namespace Http\Controllers\WaitListController;

use App\Domains\Patient\Entities\Patient;
use App\Domains\Patient\Entities\WaitList;
use App\Http\Controllers\WaitListController;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    public function rota(string $uuid): string
    {
        return route('wait-list.destroy', ['wait_list' => $uuid]);
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $uuid = $this->faker->uuid();

        $this->assertEquals(
            $this->rota($uuid),
            URL::action([WaitListController::class, 'destroy'], ['wait_list' => $uuid])
        );
    }

    #[
        Test,
        TestDox('Deve excluir um paciente na lista de espera com sucesso'),
    ]
    public function sucesso(): void
    {
        $paciente = $this->criarPaciente();
        $waitList = WaitList::factory()
            ->withPatient($paciente)
            ->create();

        $this->login();

        $this->deleteJson($this->rota($waitList->uuid))
            ->assertNoContent();

        $this->assertSoftDeleted(WaitList::class, [
            'patient_id' => $paciente->id,
        ]);
    }

    #[Test, TestDox('Deve retornar erro ao tentar cadastrar paciente inexistente na lista de espera')]
    public function erroPacienteInexistente(): void
    {
        $this->login();

        $this
            ->deleteJson($this->rota($this->faker->uuid))
            ->assertNotFound();
    }

    #[
        Test,
        TestDox('Deve retornar erro ao tentar excluir um paciente da lista de espera sem estar logado'),
    ]
    public function erroNaoLogado(): void
    {
        $waitList = WaitList::factory()
            ->withPatient()
            ->create();

        $this
            ->deleteJson($this->rota($waitList->uuid))
            ->assertUnauthorized();
    }

    private function criarPaciente(): Patient
    {
        return Patient::factory()->create();
    }
}
