<?php

namespace Http\Controllers\AppointmentController;

use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Appointment\Enums\AppointmentTimeEnum;
use App\Domains\Appointment\Enums\AppointmentTypeEnum;
use App\Domains\Patient\Entities\Patient;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class IndexTest extends TestCase
{
    public function rota(): string
    {
        return route('appointment.index');
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $this->assertEquals(
            $this->rota(),
            URL::action([AppointmentController::class, 'index'])
        );
    }

    #[
        Test,
        TestDox('Deve listar todos as consultas com sucesso'),
        DataProvider('filtroDataProvider')
    ]
    public function sucesso(array $params): void
    {
        $consultas = $this->criarConsultas();

        /** @var Appointment $appointment */
        $appointment = $consultas->first();
        $appointment->date = now()->addMonth()->startOfDay();
        $appointment->save();

        $data = collect(self::mapRecursive($params, compact('appointment')));

        if ($data->has(0)) {
            $data = null;
        }

        $this->login();

        $this
            ->getJson($this->rota() . '?' . http_build_query($data ? $data->toArray() : []))
            ->assertOk()
            ->assertJsonCount($data ? 1 : $consultas->count(), 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'uuid',
                        'date',
                        'patient' => [
                            'uuid',
                            'name',
                            'document',
                        ],
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    #[
        Test,
        TestDox('Deve retornar erro para parâmetros inválidos'),
        DataProvider('parametrosInvalidosDataProvider')
    ]
    public function erroParametrosInvalidos(array $parametro): void
    {
        $this->login();

        $this
            ->getJson($this->rota() . '?' . http_build_query($parametro))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                key($parametro)
            ]);
    }

    #[
        Test,
        TestDox('Deve retornar erro para usuário não logado'),
    ]
    public function naoLogado(): void
    {
        $this
            ->getJson($this->rota())
            ->assertUnauthorized();
    }

    private function criarConsultas(): Collection
    {
        collect(range(1, 5))->each(function() {
            Appointment::factory()
                ->withPatient()
                ->create();
        });

        return Appointment::all();
    }

    public static function filtroDataProvider(): array
    {
        return [
            'sem filtro' => [
                [null]
            ],
            'filtro por uuid' => [
                [
                    'uuid' => fn(Appointment $appointment) => $appointment->uuid
                ]
            ],
            'filtro por uuid do paciente' => [
                [
                    'patient_uuid' => fn(Appointment $appointment) => $appointment->patient->uuid]
                ],
            'filtro por date' => [
                [
                    'date' => fn(Appointment $appointment) => $appointment->date->format('Y-m-d')
                ]
            ],
        ];
    }

    public static function parametrosInvalidosDataProvider(): array
    {
        return [
            'uuid inválido' => [
                [
                    'uuid' => 'ab'
                ]
            ],
            'uuid do paciente inválidoe' => [
                [
                    'patient_uuid' => 'ab'
                ]
            ],
            'data inválido' => [
                [
                    'date' => '2026-22-14'
                ]
            ],
        ];
    }
}
