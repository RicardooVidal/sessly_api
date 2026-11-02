<?php

namespace Tests\Domains\Appointment\Jobs;

use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Appointment\Enums\AppointmentTimeEnum;
use App\Domains\Appointment\Enums\AppointmentTypeEnum;
use App\Domains\Appointment\Jobs\CreateAppointmentJob;
use App\Domains\Appointment\Repositories\AppointmentRepository;
use App\Domains\Patient\Entities\Patient;
use App\Enums\PresenceEnum;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class CreateAppointmentJobTest extends TestCase
{
    #[Test, TestDox('Deve criar consultas de acordo com a configuração do paciente e última consulta')]
    public function deveCriarConsultasDeAcordoComUltimaConsulta(): void
    {
        $dataInicial = '2026-10-05 09:00:00'; // Segunda-Feira
        $dataEsperada = '2026-10-12 09:00:00'; // Segunda-Feira da próxima semana

        $consulta = $this->criarConsultaInicial(Carbon::parse($dataInicial));
        $paciente = $consulta->patient;

        $job = new CreateAppointmentJob('public', $paciente, app(AppointmentRepository::class));

        dispatch($job);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $paciente->id,
            'date' => $dataEsperada,
            'presence' => PresenceEnum::PENDING->value
        ]);
    }

    #[Test, TestDox('Não deve criar consultas caso o paciente não tenha uma consulta inicial criada')]
    public function naoDeveCriarConsultasPacienteSemConsulta(): void
    {
        Log::spy();

        $paciente = Patient::factory()->create(['create_appointments' => true]);

        $job = new CreateAppointmentJob('public', $paciente, app(AppointmentRepository::class));

        dispatch($job);

        $this->assertDatabaseMissing('appointments', [
            'patient_id' => $paciente->id,
        ]);

        Log::shouldHaveReceived('warning');
    }

    #[Test, TestDox('Não deve criar consultas caso a consulta inicial esteja pendente')]
    public function naoDeveCriarConsultasPacienteComConsultaPendente(): void
    {
        Log::spy();

        $dataInicial = '2026-10-05 09:00:00'; // Segunda-Feira

        $consulta = $this->criarConsultaInicial(Carbon::parse($dataInicial));
        $consulta->update(['presence' => PresenceEnum::PENDING]);
        $paciente = $consulta->patient;

        $job = new CreateAppointmentJob('public', $paciente, app(AppointmentRepository::class));

        dispatch($job);

        $this->assertSame(1, Appointment::query()->where('patient_id', $paciente->id)->count());
        $this->assertDatabaseHas('appointments', [
            'id' => $consulta->id,
            'presence' => PresenceEnum::PENDING->value,
        ]);

        Log::shouldHaveReceived('warning');
    }

    #[Test, TestDox('Não deve duplicar a consulta caso o job seja executado novamente')]
    public function naoDeveDuplicarConsultaAoExecutarJobNovamente(): void
    {
        $consulta = $this->criarConsultaInicial(Carbon::parse('2026-10-05 09:00:00'));
        $paciente = $consulta->patient;

        $job = new CreateAppointmentJob('public', $paciente, app(AppointmentRepository::class));

        dispatch($job);
        dispatch($job);

        $this->assertSame(2, Appointment::query()->where('patient_id', $paciente->id)->count());
        $this->assertDatabaseHas('appointments', [
            'patient_id' => $paciente->id,
            'date' => '2026-10-12 09:00:00',
            'presence' => PresenceEnum::PENDING->value,
        ]);
    }

    #[Test, TestDox('Deve criar a próxima consulta no dia da semana configurado no paciente')]
    #[DataProvider('diasDaSemanaDataProvider')]
    public function deveCriarConsultaNoDiaDaSemanaConfigurado(int $day, string $dataInicial, string $dataEsperada): void
    {
        $paciente = Patient::factory()->create([
            'day' => $day,
            'time' => AppointmentTimeEnum::THIRTY_MINUTES,
            'type' => AppointmentTypeEnum::WEEKLY,
            'create_appointments' => true,
        ]);

        $this->criarConsultaInicial(Carbon::parse($dataInicial), $paciente);

        $job = new CreateAppointmentJob('public', $paciente, app(AppointmentRepository::class));

        dispatch($job);

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $paciente->id,
            'date' => $dataEsperada,
            'presence' => PresenceEnum::PENDING->value,
        ]);
    }

    public static function diasDaSemanaDataProvider(): array
    {
        return [
            'segunda-feira' => [0, '2026-10-05 09:00:00', '2026-10-12 09:00:00'],
            'terca-feira' => [1, '2026-10-06 09:00:00', '2026-10-13 09:00:00'],
            'quarta-feira' => [2, '2026-10-07 09:00:00', '2026-10-14 09:00:00'],
            'quinta-feira' => [3, '2026-10-08 09:00:00', '2026-10-15 09:00:00'],
            'sexta-feira' => [4, '2026-10-09 09:00:00', '2026-10-16 09:00:00'],
            'sabado' => [5, '2026-10-10 09:00:00', '2026-10-17 09:00:00'],
            'domingo' => [6, '2026-10-11 09:00:00', '2026-10-18 09:00:00'],
        ];
    }

    private function criarConsultaInicial(Carbon $date, ?Patient $patient = null): Appointment
    {
        $patient ??= Patient::factory()
            ->create([
                'day' => 0,
                'time' => AppointmentTimeEnum::THIRTY_MINUTES,
                'type' => AppointmentTypeEnum::WEEKLY,
                'create_appointments' => true,
            ]);

        return Appointment::factory()
            ->withPatient($patient)
            ->create([
                'date' => $date,
                'presence' => PresenceEnum::PRESENT,
            ]);
    }
}
