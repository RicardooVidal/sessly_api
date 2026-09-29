<?php

namespace Http\Controllers\AppointmentController;

use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Appointment\Enums\AppointmentTimeEnum;
use App\Domains\Appointment\Enums\AppointmentTypeEnum;
use App\Domains\Patient\Entities\Patient;
use App\Enums\PresenceEnum;
use App\Exceptions\PatientNotActiveException;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PatientController;
use Carbon\Carbon;
use Database\Factories\PatientFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(now()->startOfSecond());
    }

    public function rota(): string
    {
        return route('appointment.update');
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $this->assertEquals(
            $this->rota(),
            URL::action([AppointmentController::class, 'update'])
        );
    }

    #[
        Test,
        TestDox('Deve atualizar uma consulta com sucesso'),
    ]
    public function sucesso(): void
    {
        $appointment = Appointment::factory()
            ->withPatient()
            ->create();

        $this->login();

        $patient = Patient::factory()
            ->create([
                'active' => true,
            ]);

        $date = now()->addDay();

        $parametros = [
            'uuid' => $appointment->uuid,
            'date' => $date->format('Y-m-d'),
            'presence' => $this->faker->randomElement(PresenceEnum::cases())->value,
            'value' => 35.50,
            'notes' => $this->faker->text,
            'patient' => [
                'uuid' => $patient->uuid,
            ],
        ];

        $response = $this
            ->putJson($this->rota(), $parametros)
            ->assertOk();

        $response->assertJsonPath('data.date', $date->clone()->startOfDay()->toW3cString());
        $response->assertJsonPath('data.patient.uuid', $patient->uuid);
        $response->assertJsonPath('data.patient.name', $patient->name);
        $response->assertJsonPath('data.presence', $parametros['presence']);
        $response->assertJsonPath('data.value', $parametros['value']);
        $response->assertJsonPath('data.notes', $parametros['notes']);
    }

    #[
        Test,
        TestDox('Não deve atualizar consulta para um paciente inativo'),
    ]
    public function erroPacienteInativo(): void
    {
        $appointment = Appointment::factory()
            ->withPatient()
            ->create();

        /** @var Patient $patient */
        $patient = Patient::factory()->create(['active' => false]);

        $parametros = [
            'appointment' => $appointment->uuid,
            'patient' => [
                'uuid' => $patient->uuid,
            ],
            'date' => now()->addDay()->format('Y-m-d'),
            'presence' => $this->faker->randomElement(PresenceEnum::cases())->value,
            'value' => 35.50,
            'notes' => $this->faker->text,
        ];

        $this->login();
        $this->putJson($this->rota(), $parametros)
            ->assertNotAcceptable();
    }

    #[
        Test,
        TestDox('Não deve atualizar uma consulta sem estar logado'),
    ]
    public function erroNaoLogado(): void
    {
        $appointment = Appointment::factory()
            ->withPatient()
            ->create();

        $parametros = [
            'appointment' => $appointment->uuid,
            'patient' => [
                'uuid' => $appointment->patient->uuid,
            ],
            'date' => now()->addDay()->format('Y-m-d'),
            'presence' => $this->faker->randomElement(PresenceEnum::cases())->value,
            'value' => 35.50,
            'notes' => $this->faker->text,
        ];

        $this->putJson($this->rota(), $parametros)
            ->assertUnauthorized();
    }

    #[
        Test,
        TestDox('Deve retornar erro para parâmetros inválidos'),
        DataProvider('parametrosInvalidosDataProvider')
    ]
    public function parametrosInvalidos(array $parametro): void
    {
        $appointment = Appointment::factory()
            ->withPatient()
            ->create();

        $parametro['appointment'] = $appointment->uuid;

        $this->login();

        $this
            ->putJson($this->rota(), $parametro)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                array_key_first($parametro),
            ]);
    }

    public static function parametrosInvalidosDataProvider(): array
    {
        return [
            'patient inválido' => [
                ['patient' => null]
            ],
            'date inválido' => [
                ['date' => 'invalid-date']
            ],
            'date no passado' => [
                ['date' => now()->subDays(5)->format('Y-m-d')]
            ],
            'medical_record inválido' => [
                ['medical_record' => ['invalid-record']]
            ],
            'notes inválido' => [
                ['notes' => ['invalid-notes']]
            ],
            'presence inválido' => [
                ['presence' => 'aaa']
            ],
            'value inválido' => [
                ['value' => 'aaa']
            ],
        ];
    }
}
