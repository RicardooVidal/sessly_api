<?php

namespace Tests\Http\Controllers\PatientController;

use App\Domains\Patient\Entities\Patient;
use App\Http\Controllers\PatientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * @see PatientController::getOptionsForAppointment()
 */
class GetOptionsForAppointmentTest extends TestCase
{
    public function rota(string $uuid): string
    {
        return route('patient.options-appointments', ['patient' => $uuid]);
    }

    #[Test, TestDox('Deve direcionar a requisição para a rota de opções de consulta')]
    public function rotaCorreta(): void
    {
        $uuid = $this->faker->uuid;
        $url = $this->rota($uuid);

        $this->assertEquals(
            $url,
            URL::action([PatientController::class, 'getOptionsForAppointment'], [
                'patient' => $uuid,
            ])
        );

        $this->assertSame(
            PatientController::class . '@getOptionsForAppointment',
            Route::getRoutes()->match(Request::create($url))->getActionName()
        );
    }

    #[Test, TestDox('Deve retornar as opções de consulta do paciente solicitado')]
    public function sucesso(): void
    {
        $this->login();

        Patient::factory()->create(['day' => 1, 'value' => 90.00]);
        $patient = Patient::factory()->create(['day' => 3, 'value' => 150.50]);

        $this->getJson($this->rota($patient->uuid))
            ->assertOk()
            ->assertJsonPath('data.uuid', $patient->uuid)
            ->assertJsonPath('data.day', 3)
            ->assertJsonPath('data.value', 150.50);
    }

    #[Test, TestDox('Deve retornar 404 para paciente inexistente')]
    public function erroPacienteInexistente(): void
    {
        $this->login();

        $this->getJson($this->rota($this->faker->uuid))
            ->assertNotFound();
    }

    #[Test, TestDox('Deve retornar erro ao consultar opções sem estar logado')]
    public function erroNaoLogado(): void
    {
        $patient = Patient::factory()->create();

        $this->getJson($this->rota($patient->uuid))
            ->assertUnauthorized();
    }

}
