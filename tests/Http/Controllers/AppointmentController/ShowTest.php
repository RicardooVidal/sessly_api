<?php

namespace Http\Controllers\AppointmentController;

use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Appointment\Enums\AppointmentTimeEnum;
use App\Domains\Appointment\Enums\AppointmentTypeEnum;
use App\Domains\Patient\Entities\Patient;
use App\Http\Controllers\AppointmentController;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ShowTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(now()->startOfSecond());
    }

    public function rota(string $uuid): string
    {
        return route('appointment.show', ['appointment' => $uuid]);
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $uuid = $this->faker->uuid;

        $this->assertEquals(
            $this->rota($uuid),
            URL::action([AppointmentController::class, 'show'], ['appointment' => $uuid])
        );
    }

    #[
        Test,
        TestDox('Deve retornar uma consulta com sucesso'),
    ]
    public function sucesso(): void
    {
        $appointment = Appointment::factory()
            ->withPatient()
            ->create();

        $this->login();

        $response = $this
            ->getJson($this->rota($appointment->uuid))
            ->assertJsonFragment([
                'data' => [
                    'uuid' => $appointment->uuid,
                    'date' => $appointment->date->toW3cString(),
                    'presence' => $appointment->presence->value,
                    'value' => $appointment->value,
                    'notes' => $appointment->notes,
                    'patient' => [
                        'uuid' => $appointment->patient->uuid,
                        'name' => $appointment->patient->name,
                        'type' => $appointment->patient->type->value,
                        'time' => $appointment->patient->time->value,
                        'email' => null,
                        'gender' => null,
                        'phone' => null,
                        'active' => null,
                        'notes' => null,
                        'document' => null,
                    ],
                ]
            ])
            ->assertOk();
    }

    #[
        Test,
        TestDox('Não deve exibir uma consulta com um uuid inexistente'),
    ]
    public function erro(): void
    {
        $this->login();
        $this->deleteJson($this->rota($this->faker->uuid))
            ->assertNotFound();
    }

    #[
        Test,
        TestDox('Não deve exibir uma consulta sem estar logado'),
    ]
    public function erroNaoLogado(): void
    {
        $appointment = Appointment::factory()
            ->withPatient()
            ->create();

        $this->deleteJson($this->rota($appointment->uuid))
            ->assertUnauthorized();
    }
}
