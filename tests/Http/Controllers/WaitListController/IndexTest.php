<?php

namespace Http\Controllers\WaitListController;

use App\Domains\Patient\Entities\Patient;
use App\Domains\Patient\Entities\WaitList;
use App\Http\Controllers\WaitListController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class IndexTest extends TestCase
{
    public function rota(): string
    {
        return route('wait-list.index');
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $this->assertEquals(
            $this->rota(),
            URL::action([WaitListController::class, 'index'])
        );
    }

    #[Test, TestDox('Deve listar os pacientes da lista de espera com sucesso')]
    public function sucesso(): void
    {
        $patients = $this->criarPacientesNaListaDeEspera();

        $removedPatient = Patient::factory()->create();
        WaitList::factory()
            ->withPatient($removedPatient)
            ->create()
            ->delete();

        $this->login();

        $this
            ->getJson($this->rota())
            ->assertOk()
            ->assertJsonCount($patients->count(), 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'uuid',
                        'patient_uuid',
                        'created_at',
                        'patient' => [
                            'uuid',
                            'name',
                        ],
                    ],
                ],
            ])
            ->assertJsonMissing([
                'patient_uuid' => $removedPatient->uuid,
            ]);
    }

    #[Test, TestDox('Deve retornar erro ao listar a lista de espera sem estar logado')]
    public function erroNaoLogado(): void
    {
        $this->getJson($this->rota())
            ->assertUnauthorized();
    }

    private function criarPacientesNaListaDeEspera(): Collection
    {
        return collect(range(1, 3))->map(function () {
            $patient = Patient::factory()->create();

            return WaitList::factory()
                ->withPatient($patient)
                ->create();
        });
    }
}
