<?php

namespace Tests\Http\Controllers\PatientController;

use App\Domains\Patient\Entities\Patient;
use App\Domains\Patient\Entities\WaitList;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

class SelectTest extends IndexTest
{
    public function rota(): string
    {
        return route('patient.select');
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $this->assertEquals(
            $this->rota(),
            URL::action([PatientController::class, 'select'])
        );
    }

    #[
        Test,
        TestDox('Deve listar todos os pacientes com sucesso'),
        DataProvider('filtroDataProvider')
    ]
    public function sucesso(array $params): void
    {
        $patients = $this->criarPacientes();

        /** @var Patient $patient */
        $patient = $patients->first();

        $data = collect(self::mapRecursive($params, compact('patient')));

        if (! empty($params['active'])) {
            $patient->update(['active' => false]);
        }

        if ($data->has(0)) {
            $data = null;
        }

        $this->login();

        //        dd($patient->count());

        $this
            ->getJson($this->rota().'?'.http_build_query($data ? $data->toArray() : []))
            ->assertOk()
            ->assertJsonCount($data ? 1 : $patients->count(), 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'uuid',
                        'name',
                    ],
                ],
            ]);
    }

    #[Test, TestDox('Deve ocultar pacientes da lista de espera quando solicitado')]
    public function excluiPacientesDaListaDeEspera(): void
    {
        $patients = $this->criarPacientes();
        $waitingPatient = $patients->first();

        WaitList::factory()
            ->withPatient($waitingPatient)
            ->create();

        $this->login();

        $this
            ->getJson($this->rota().'?'.http_build_query([
                'active' => 1,
                'exclude_wait_list' => 1,
            ]))
            ->assertOk()
            ->assertJsonCount($patients->count() - 1, 'data')
            ->assertJsonMissing(['uuid' => $waitingPatient->uuid]);
    }

    #[Test, TestDox('Deve manter pacientes da lista de espera sem o filtro')]
    public function mantemPacientesDaListaDeEsperaSemFiltro(): void
    {
        $patients = $this->criarPacientes();
        $waitingPatient = $patients->first();

        WaitList::factory()
            ->withPatient($waitingPatient)
            ->create();

        $this->login();

        $this
            ->getJson($this->rota().'?'.http_build_query(['active' => 1]))
            ->assertOk()
            ->assertJsonCount($patients->count(), 'data')
            ->assertJsonFragment(['uuid' => $waitingPatient->uuid]);
    }

    #[Test, TestDox('Não deve ocultar paciente com entrada removida da lista de espera')]
    public function naoOcultaPacienteComEntradaRemovida(): void
    {
        $patients = $this->criarPacientes();
        $waitingPatient = $patients->first();

        WaitList::factory()
            ->withPatient($waitingPatient)
            ->create()
            ->delete();

        $this->login();

        $this
            ->getJson($this->rota().'?'.http_build_query([
                'active' => 1,
                'exclude_wait_list' => 1,
            ]))
            ->assertOk()
            ->assertJsonCount($patients->count(), 'data')
            ->assertJsonFragment(['uuid' => $waitingPatient->uuid]);
    }

    #[Test, TestDox('Deve validar o parâmetro de exclusão da lista de espera')]
    public function validaParametroExclusaoListaDeEspera(): void
    {
        $this->login();

        $this
            ->getJson($this->rota().'?'.http_build_query([
                'exclude_wait_list' => 'invalido',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['exclude_wait_list']);
    }
}
