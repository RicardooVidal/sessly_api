<?php

namespace Tests\Http\Controllers\PatientController;

use App\Domains\Appointment\Enums\AppointmentTimeEnum;
use App\Domains\Appointment\Enums\AppointmentTypeEnum;
use App\Domains\Patient\Entities\Patient;
use App\Enums\GenderEnum;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class StoreTest extends TestCase
{
    public function rota(): string
    {
        return route('patient.store');
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $this->assertEquals(
            $this->rota(),
            URL::action([PatientController::class, 'store'])
        );
    }

    #[
        Test,
        TestDox('Deve cadastrar um paciente com sucesso'),
    ]
    public function sucesso(): void
    {
        $parametros = [
            'name' => $this->faker->name,
            'document' => $this->faker->cpf(false),
            'phone' => $this->faker->phoneNumber,
            'gender' => $this->faker->randomElement(GenderEnum::cases()),
            'day' => 3,
            'email' => $this->faker->email,
            'active' => true,
            'create_appointments' => true,
            'notify_appointment' => false,
            'notes' => $this->faker->text,
            'value' => $this->faker->randomFloat(),
            'type' => $this->faker->randomElement(AppointmentTypeEnum::cases()),
            'time' => $this->faker->randomElement(AppointmentTimeEnum::cases()),
        ];

        $this->login();

        $this
            ->postJson($this->rota(), $parametros)
            ->assertCreated()
            ->assertJsonFragment([
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
                    'name',
                    'document',
                    'phone',
                    'gender',
                    'day',
                    'email',
                    'active',
                    'create_appointments',
                    'notify_appointment',
                    'value',
                    'notes',
                    'type',
                    'time',
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
            ->postJson($this->rota(), $parametro)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                array_key_first($parametro),
            ]);
    }

    #[
        Test,
        TestDox('Deve retornar erro ao tentar cadastrar paciente sem estar logado'),
    ]
    public function erroNaoLogado(): void
    {
        $parametros = [
            'name' => $this->faker->name,
            'document' => $this->faker->cpf(false),
            'phone' => $this->faker->phoneNumber,
            'gender' => $this->faker->randomElement(GenderEnum::cases()),
            'day' => 3,
            'email' => $this->faker->email,
            'active' => true,
            'create_appointments' => true,
            'notify_appointment' => true,
            'value' => $this->faker->randomFloat(),
            'notes' => $this->faker->text,
            'type' => $this->faker->randomElement(AppointmentTypeEnum::cases()),
            'time' => $this->faker->randomElement(AppointmentTimeEnum::cases()),
        ];

        $this
            ->postJson($this->rota(), $parametros)
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
            ] ,
            'day inválido' => [
                ['day' => 'inválido']
            ],
            'gender inválido' => [
                ['gender' => 'invalido']
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
}
