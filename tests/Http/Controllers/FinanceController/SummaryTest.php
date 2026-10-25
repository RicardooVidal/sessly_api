<?php

namespace Http\Controllers\FinanceController;

use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Patient\Entities\Patient;
use App\Enums\PresenceEnum;
use App\Http\Controllers\FinanceController;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class SummaryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00'));
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function rota(): string
    {
        return route('finance.summary');
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $this->assertEquals(
            $this->rota(),
            URL::action([FinanceController::class, 'summary'])
        );
    }

    #[Test, TestDox('Deve resumir o mes considerando apenas consultas presentes e o fallback de valor')]
    public function resumoMensal(): void
    {
        $patientA = Patient::factory()->create(['value' => 100.0]);
        $patientB = Patient::factory()->create(['value' => 200.0]);

        $this->criarConsulta($patientA, PresenceEnum::PRESENT, '2026-10-02 09:00:00', 50.0);
        $this->criarConsulta($patientA, PresenceEnum::PRESENT, '2026-10-10 09:00:00', null);
        $this->criarConsulta($patientA, PresenceEnum::ABSENT, '2026-10-05 09:00:00', 30.0);
        $this->criarConsulta($patientB, PresenceEnum::PRESENT, '2026-10-20 09:00:00', 80.0);
        $this->criarConsulta($patientB, PresenceEnum::PENDING, '2026-10-06 09:00:00', 999.0);
        $this->criarConsulta($patientB, PresenceEnum::PRESENT, '2026-09-30 09:00:00', 500.0);
        $this->criarConsulta($patientB, PresenceEnum::PRESENT, '2026-11-01 09:00:00', 600.0);

        $this->login();

        $this->getJson($this->rota().'?period=month')
            ->assertOk()
            ->assertJsonPath('data.period', 'month')
            ->assertJsonPath('data.starts_at', Carbon::parse('2026-10-01 00:00:00')->toW3cString())
            ->assertJsonPath('data.ends_at', Carbon::parse('2026-10-31 23:59:59')->toW3cString())
            ->assertJsonPath('data.appointments_count', 3)
            ->assertJsonPath('data.receivable_amount', 230)
            ->assertJsonPath('data.average_ticket', 76.67)
            ->assertJsonPath('data.patients_count', 2)
            ->assertJsonPath('data.absences_count', 1)
            ->assertJsonPath('data.attendance_rate', 75);
    }

    #[Test, TestDox('Deve resumir a semana de segunda a domingo')]
    public function resumoSemanal(): void
    {
        $patient = Patient::factory()->create(['value' => 999.0]);

        $this->criarConsulta($patient, PresenceEnum::PRESENT, '2026-10-12 00:00:00', 40.0);
        $this->criarConsulta($patient, PresenceEnum::PRESENT, '2026-10-18 23:59:59', 60.0);
        $this->criarConsulta($patient, PresenceEnum::PRESENT, '2026-10-11 23:59:59', 100.0);
        $this->criarConsulta($patient, PresenceEnum::PRESENT, '2026-10-19 00:00:00', 100.0);
        $this->criarConsulta($patient, PresenceEnum::ABSENT, '2026-10-14 09:00:00', 20.0);

        $this->login();

        $this->getJson($this->rota().'?period=week')
            ->assertOk()
            ->assertJsonPath('data.period', 'week')
            ->assertJsonPath('data.starts_at', Carbon::parse('2026-10-12 00:00:00')->toW3cString())
            ->assertJsonPath('data.ends_at', Carbon::parse('2026-10-18 23:59:59')->toW3cString())
            ->assertJsonPath('data.appointments_count', 2)
            ->assertJsonPath('data.receivable_amount', 100)
            ->assertJsonPath('data.average_ticket', 50)
            ->assertJsonPath('data.patients_count', 1)
            ->assertJsonPath('data.absences_count', 1)
            ->assertJsonPath('data.attendance_rate', 66.67);
    }

    #[Test, TestDox('Deve usar o mes atual quando o periodo nao for informado')]
    public function periodoPadraoEhMesAtual(): void
    {
        $patient = Patient::factory()->create();

        $this->criarConsulta($patient, PresenceEnum::PRESENT, '2026-10-15 09:00:00', 35.5);

        $this->login();

        $this->getJson($this->rota())
            ->assertOk()
            ->assertJsonPath('data.period', 'month')
            ->assertJsonPath('data.appointments_count', 1);
    }

    #[Test, TestDox('Deve navegar para o periodo da data de referencia')]
    public function navegacaoPorDataDeReferencia(): void
    {
        $this->login();

        $this->getJson($this->rota().'?period=week&date=2026-10-20')
            ->assertOk()
            ->assertJsonPath('data.starts_at', Carbon::parse('2026-10-19 00:00:00')->toW3cString())
            ->assertJsonPath('data.ends_at', Carbon::parse('2026-10-25 23:59:59')->toW3cString());
    }

    #[Test, TestDox('Deve retornar zeros quando nao houver consultas no periodo')]
    public function periodoVazio(): void
    {
        $this->login();

        $this->getJson($this->rota().'?period=month')
            ->assertOk()
            ->assertJsonPath('data.appointments_count', 0)
            ->assertJsonPath('data.receivable_amount', 0)
            ->assertJsonPath('data.average_ticket', 0)
            ->assertJsonPath('data.patients_count', 0)
            ->assertJsonPath('data.absences_count', 0)
            ->assertJsonPath('data.attendance_rate', 0);
    }

    #[
        Test,
        TestDox('Deve retornar erro para parametros invalidos'),
        DataProvider('parametrosInvalidosDataProvider')
    ]
    public function parametrosInvalidos(array $parametros): void
    {
        $this->login();

        $this->getJson($this->rota().'?'.http_build_query($parametros))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                array_key_first($parametros),
            ]);
    }

    #[Test, TestDox('Deve retornar erro para usuario nao logado')]
    public function naoLogado(): void
    {
        $this->getJson($this->rota())
            ->assertUnauthorized();
    }

    private function criarConsulta(
        Patient $patient,
        PresenceEnum $presence,
        string $date,
        ?float $value
    ): Appointment {
        return Appointment::factory()
            ->withPatient($patient)
            ->create([
                'date' => Carbon::parse($date),
                'presence' => $presence->value,
                'value' => $value,
            ]);
    }

    public static function parametrosInvalidosDataProvider(): array
    {
        return [
            'periodo invalido' => [
                ['period' => 'day'],
            ],
            'data invalida' => [
                ['date' => 'not-a-date'],
            ],
        ];
    }
}
