<?php

namespace Http\Controllers\DashboardController;

use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Contract\Entities\Contract;
use App\Domains\Patient\Entities\Patient;
use App\Domains\Patient\Entities\WaitList;
use App\Enums\PresenceEnum;
use App\Http\Controllers\DashboardController;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class SummaryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00'));
        Patient::query()->forceDelete();
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function rota(): string
    {
        return route('dashboard.summary');
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $this->assertEquals(
            $this->rota(),
            URL::action([DashboardController::class, 'summary'])
        );
    }

    #[Test, TestDox('Deve resumir o dia, a semana, os pacientes e os proximos atendimentos')]
    public function resumo(): void
    {
        $patientA = Patient::factory()->create();
        $patientB = Patient::factory()->create();
        $inactivePatient = Patient::factory()->create(['active' => false]);

        Carbon::setTestNow(Carbon::parse('2026-09-20 10:00:00'));
        $oldPatient = Patient::factory()->create();
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00'));

        $this->criarConsulta($patientA, PresenceEnum::PRESENT, '2026-10-15 09:00:00');
        $this->criarConsulta($patientB, PresenceEnum::ABSENT, '2026-10-15 10:00:00');
        $this->criarConsulta($patientB, PresenceEnum::PENDING, '2026-10-15 11:00:00');
        $this->criarConsulta($patientA, PresenceEnum::PENDING, '2026-10-15 15:00:00');
        $this->criarConsulta($patientB, PresenceEnum::PENDING, '2026-10-15 17:00:00');
        $this->criarConsulta($patientA, PresenceEnum::PRESENT, '2026-10-12 09:00:00');
        $this->criarConsulta($patientB, PresenceEnum::ABSENT, '2026-10-18 10:00:00');
        $this->criarConsulta($patientA, PresenceEnum::PRESENT, '2026-10-11 23:59:59');
        $this->criarConsulta($patientB, PresenceEnum::PRESENT, '2026-10-19 00:00:00');

        Contract::factory()->withPatient($patientA)->create([
            'valid_until' => Carbon::parse('2026-10-30 10:00:00'),
        ]);
        Contract::factory()->withPatient($patientB)->create([
            'valid_until' => Carbon::parse('2026-11-14 10:00:00'),
        ]);
        Contract::factory()->withPatient($patientA)->create([
            'valid_until' => Carbon::parse('2026-10-14 10:00:00'),
        ]);
        Contract::factory()->withPatient($patientB)->create([
            'valid_until' => Carbon::parse('2026-11-15 10:00:00'),
        ]);

        WaitList::factory()->withPatient($patientA)->create();
        $deletedWaitList = WaitList::factory()->withPatient($patientB)->create();
        $deletedWaitList->delete();

        $this->login();

        $this->getJson($this->rota())
            ->assertOk()
            ->assertJsonPath('data.generated_at', Carbon::parse('2026-10-15 12:00:00')->toW3cString())
            ->assertJsonPath('data.today.starts_at', Carbon::parse('2026-10-15 00:00:00')->toW3cString())
            ->assertJsonPath('data.today.ends_at', Carbon::parse('2026-10-15 23:59:59')->toW3cString())
            ->assertJsonPath('data.today.appointments_count', 5)
            ->assertJsonPath('data.today.present_count', 1)
            ->assertJsonPath('data.today.pending_count', 3)
            ->assertJsonPath('data.today.absent_count', 1)
            ->assertJsonPath('data.today.attendance_rate', 50)
            ->assertJsonPath('data.week.starts_at', Carbon::parse('2026-10-12 00:00:00')->toW3cString())
            ->assertJsonPath('data.week.ends_at', Carbon::parse('2026-10-18 23:59:59')->toW3cString())
            ->assertJsonPath('data.week.appointments_count', 7)
            ->assertJsonPath('data.week.present_count', 2)
            ->assertJsonPath('data.week.pending_count', 3)
            ->assertJsonPath('data.week.absent_count', 2)
            ->assertJsonPath('data.week.attendance_rate', 50)
            ->assertJsonPath('data.patients.active_count', 3)
            ->assertJsonPath('data.patients.new_this_month', 3)
            ->assertJsonPath('data.contracts.expiring_in_thirty_days', 2)
            ->assertJsonPath('data.wait_list.count', 1)
            ->assertJsonCount(2, 'data.next_appointments')
            ->assertJsonPath('data.next_appointments.0.uuid', Appointment::query()
                ->where('date', '2026-10-15 15:00:00')
                ->firstOrFail()
                ->uuid)
            ->assertJsonPath('data.next_appointments.0.patient.uuid', $patientA->uuid)
            ->assertJsonPath('data.next_appointments.0.patient.name', $patientA->name)
            ->assertJsonPath('data.next_appointments.1.uuid', Appointment::query()
                ->where('date', '2026-10-15 17:00:00')
                ->firstOrFail()
                ->uuid)
            ->assertJsonPath('data.next_appointments.1.patient.uuid', $patientB->uuid)
            ->assertJsonPath('data.next_appointments.1.patient.name', $patientB->name);
    }

    #[Test, TestDox('Deve retornar zeros quando nao houver dados')]
    public function resumoVazio(): void
    {
        $this->login();

        $this->getJson($this->rota())
            ->assertOk()
            ->assertJsonPath('data.today.appointments_count', 0)
            ->assertJsonPath('data.today.present_count', 0)
            ->assertJsonPath('data.today.pending_count', 0)
            ->assertJsonPath('data.today.absent_count', 0)
            ->assertJsonPath('data.today.attendance_rate', 0)
            ->assertJsonPath('data.week.appointments_count', 0)
            ->assertJsonPath('data.week.attendance_rate', 0)
            ->assertJsonPath('data.patients.active_count', 0)
            ->assertJsonPath('data.patients.new_this_month', 0)
            ->assertJsonPath('data.contracts.expiring_in_thirty_days', 0)
            ->assertJsonPath('data.wait_list.count', 0)
            ->assertJsonCount(0, 'data.next_appointments');
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
        string $date
    ): Appointment {
        return Appointment::factory()
            ->withPatient($patient)
            ->create([
                'presence' => $presence,
                'date' => Carbon::parse($date),
            ]);
    }
}
