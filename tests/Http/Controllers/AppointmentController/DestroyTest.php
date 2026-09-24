<?php

namespace Tests\Http\Controllers\AppointmentController;

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

class DestroyTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(now()->startOfSecond());
    }

    public function rota(string $uuid): string
    {
        return route('appointment.destroy', ['appointment' => $uuid]);
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $uuid = $this->faker->uuid;

        $this->assertEquals(
            $this->rota($uuid),
            URL::action([AppointmentController::class, 'destroy'], ['appointment' => $uuid])
        );
    }

    #[
        Test,
        TestDox('Deve remover uma consulta com sucesso'),
    ]
    public function sucesso(): void
    {
        $appointment = Appointment::factory()
            ->withPatient()
            ->create();

        $this->login();

        $response = $this
            ->deleteJson($this->rota($appointment->uuid))
            ->assertOk();

        $this->assertSoftDeleted(
            'appointments',
            [
                'uuid' => $appointment->uuid,
            ]
        );
    }

    #[
        Test,
        TestDox('Não deve excluir uma consulta com um uuid inexistente'),
    ]
    public function erro(): void
    {
        $this->login();
        $this->deleteJson($this->rota($this->faker->uuid))
            ->assertNotFound();
    }

    #[
        Test,
        TestDox('Não deve excluir uma consulta sem estar logado'),
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
