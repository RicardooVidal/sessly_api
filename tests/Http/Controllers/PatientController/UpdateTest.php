<?php

namespace Tests\Http\Controllers\PatientController;

use App\Domains\Appointment\Enums\AppointmentTimeEnum;
use App\Domains\Appointment\Enums\AppointmentTypeEnum;
use App\Domains\Patient\Entities\Patient;
use App\Enums\GenderEnum;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    public function rota(): string
    {
        return route('patient.update');
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $this->assertEquals(
            $this->rota(),
            URL::action([PatientController::class, 'update'])
        );
    }

    #[
        Test,
        TestDox('Deve atualizar um paciente com sucesso'),
    ]
    public function sucesso(): void
    {
        $patient = $this->criarPaciente();

        $parametros = [
            'uuid' => $patient->uuid,
            'name' => $this->faker->name,
            'document' => $this->faker->cpf(false),
            'phone' => $this->faker->phoneNumber,
            'gender' => $this->faker->randomElement(GenderEnum::cases()),
            'day' => 3,
            'email' => $this->faker->email,
            'active' => false,
            'create_appointments' => true,
            'notify_appointment' => false,
            'notes' => $this->faker->text,
            'value' => $this->faker->randomFloat(),
            'type' => $this->faker->randomElement(AppointmentTypeEnum::cases()),
            'time' => $this->faker->randomElement(AppointmentTimeEnum::cases()),
        ];

        $this->login();

        $this
            ->putJson($this->rota(), $parametros)
            ->assertOk()
            ->assertJsonFragment([
                'uuid' => $patient->uuid,
                'name' => $parametros['name'],
                'document' => $parametros['document'],
                'phone' => $parametros['phone'],
                'gender' => $parametros['gender'],
                'day' => $parametros['day'],
                'email' => $parametros['email'],
                'active' => $parametros['active'],
                'create_appointments' => $parametros['create_appointments'],
                'notify_appointment' => $parametros['notify_appointment'],
                'notes' => $parametros['notes'],
                'value' => $parametros['value'],
                'type' => $parametros['type'],
                'time' => $parametros['time'],
            ])
            ->assertJsonStructure([
                'data' => [
                    'uuid',
                    'document',
                    'phone',
                    'gender',
                    'day',
                    'email',
                    'active',
                    'create_appointments',
                    'notify_appointment',
                    'notes',
                    'value',
                    'type',
                    'time',
                ]
            ]);
    }

    #[Test, TestDox('Deve retornar 404 para paciente inexistente')]
    public function pacienteInexistente(): void
    {
        $this->login();

        $parametros = [
            'uuid' => $this->faker->uuid,
            'name' => $this->faker->name,
            'document' => $this->faker->cpf(false),
            'phone' => $this->faker->phoneNumber,
            'gender' => $this->faker->randomElement(GenderEnum::cases()),
            'day' => 3,
            'email' => $this->faker->email,
            'active' => false,
            'create_appointments' => true,
            'notify_appointment' => false,
            'notes' => $this->faker->text,
            'value' => $this->faker->randomFloat(),
            'type' => $this->faker->randomElement(AppointmentTypeEnum::cases()),
            'time' => $this->faker->randomElement(AppointmentTimeEnum::cases()),
        ];

        $this
            ->putJson($this->rota(), $parametros)
            ->assertNotFound();
    }

    #[
        Test,
        TestDox('Deve retornar erro para parâmetros inválidos'),
        DataProvider('parametrosInvalidosDataProvider')
    ]
    public function erroParametrosInvalidos(array $parametro): void
    {
        $paciente = $this->criarPaciente();
        $parametro['uuid'] = $paciente->uuid;

        $this->login();

        $this
            ->putJson($this->rota(), $parametro)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                array_key_first($parametro),
            ]);
    }

    #[Test, TestDox('Deve retornar erro ao tentar atualizar um paciente sem estar logado')]
    public function erroNaoLogado(): void
    {
        $patient = $this->criarPaciente();

        $parametros = [
            'uuid' => $patient->uuid,
            'name' => $this->faker->name,
            'document' => $this->faker->cpf(false),
            'phone' => $this->faker->phoneNumber,
            'gender' => $this->faker->randomElement(GenderEnum::cases()),
            'day' => 3,
            'email' => $this->faker->email,
            'active' => false,
            'create_appointments' => true,
            'notify_appointment' => false,
            'notes' => $this->faker->text,
            'value' => $this->faker->randomFloat(),
            'type' => $this->faker->randomElement(AppointmentTypeEnum::cases()),
            'time' => $this->faker->randomElement(AppointmentTimeEnum::cases()),
        ];

        $this
            ->putJson($this->rota(), $parametros)
            ->assertUnauthorized();
    }

    public static function parametrosInvalidosDataProvider(): array
    {
        return [
            'name inválido' => [
                ['name' => true]
            ],
            'name min' => [
                ['name' => 'a']
            ],
            'name max' => [
                ['name' => str_repeat('a', 256)]
            ],
            'document inválido' => [
                ['document' => true]
            ],
            'document min' => [
                ['document' => '123456789']
            ],
            'phone inválido' => [
                ['phone' => true]
            ],
            'phone min' => [
                ['phone' => '1234']
            ],
            'phone max' => [
                ['phone' => '1234567890123456']
            ],
            'email inválido' => [
                ['email' => true]
            ],
            'email max' => [
                ['email' => str_repeat('a', 101)]
            ],
            'day inválido' => [
                ['day' => 'inválido']
            ],
            'gender inválido' => [
                ['gender' => 'invalido']
            ],
            'active inválido' => [
                ['active' => 'invalido']
            ],
            'create_appointments inválido' => [
                ['create_appointments' => 'teste']
            ],
            'notify_appointment inválido' => [
                ['notify_appointment' => 'teste']
            ],
            'value inválido' => [
                ['value' => 'inválido']
            ],
            'notes inválido' => [
                ['notes' => true]
            ],
            'type inválido' => [
                ['type' => 'aaa']
            ],
            'time inválido' => [
                ['time' => true]
            ]
        ];
    }

    private function criarPaciente(): Patient
    {
        return Patient::factory()->create();
    }
}
