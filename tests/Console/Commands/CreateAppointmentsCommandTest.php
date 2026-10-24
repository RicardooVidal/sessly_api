<?php

namespace Tests\Console\Commands;

use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Appointment\Jobs\CreateAppointmentJob;
use App\Domains\Patient\Entities\Patient;
use Illuminate\Bus\PendingBatch;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use ReflectionProperty;
use Tests\TestCase;

class CreateAppointmentsCommandTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Bus::fake();
        config(['commands.appointments.enabled' => true]);
    }

    #[Test, TestDox('Deve despachar um job por paciente elegivel na fila configurada')]
    public function deveDespacharJobsParaPacientesComConsultas(): void
    {
        config(['commands.appointments.queue' => 'test_appointments']);

        $patients = Patient::factory()->count(2)->create(['create_appointments' => true]);

        foreach ($patients as $patient) {
            Appointment::factory()->count(2)->withPatient($patient)->create();
        }

        $this->artisan('app:create-appointments')->assertSuccessful();

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatch $batch) use ($patients) {
            $this->assertSame('test_appointments', $batch->options['queue']);
            $this->assertCount(2, $batch->jobs);

            $patientProperty = new ReflectionProperty(CreateAppointmentJob::class, 'patient');
            $patientIds = $batch->jobs->map(function ($job) use ($patientProperty) {
                $this->assertInstanceOf(CreateAppointmentJob::class, $job);

                return $patientProperty->getValue($job)->id;
            })->all();

            $this->assertEqualsCanonicalizing($patients->modelKeys(), $patientIds);

            return true;
        });
    }

    #[Test, TestDox('Nao deve despachar jobs para paciente com create_appointments desativado')]
    public function naoDeveDespacharJobsParaPacienteComOpcaoDesativada(): void
    {
        $patient = Patient::factory()->create(['create_appointments' => false]);
        Appointment::factory()->withPatient($patient)->create();

        $this->artisan('app:create-appointments')->assertSuccessful();

        Bus::assertNothingBatched();
    }

    #[Test, TestDox('Nao deve despachar jobs para paciente sem consulta inicial')]
    public function naoDeveDespacharJobsParaPacienteSemConsulta(): void
    {
        Patient::factory()->create(['create_appointments' => true]);

        $this->artisan('app:create-appointments')->assertSuccessful();

        Bus::assertNothingBatched();
    }

    #[Test, TestDox('Nao deve despachar jobs quando o comando estiver desativado')]
    public function naoDeveDespacharJobsQuandoComandoDesativado(): void
    {
        config(['commands.appointments.enabled' => false]);

        $patient = Patient::factory()->create(['create_appointments' => true]);
        Appointment::factory()->withPatient($patient)->create();

        $this->artisan('app:create-appointments')->assertSuccessful();

        Bus::assertNothingBatched();
    }

    #[Test, TestDox('Deve dividir os jobs de acordo com o tamanho configurado do batch')]
    public function deveRespeitarTamanhoConfiguradoDoBatch(): void
    {
        config(['commands.appointments.batch' => 2]);

        $patients = Patient::factory()->count(3)->create(['create_appointments' => true]);

        foreach ($patients as $patient) {
            Appointment::factory()->withPatient($patient)->create();
        }

        $this->artisan('app:create-appointments')->assertSuccessful();

        Bus::assertBatchCount(2);
        Bus::assertBatched(fn (PendingBatch $batch) => $batch->jobs->count() === 2);
        Bus::assertBatched(fn (PendingBatch $batch) => $batch->jobs->count() === 1);
    }

    #[Test, TestDox('Deve agendar o comando para todos os domingos')]
    public function deveAgendarComandoAosDomingos(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'app:create-appointments'));

        $this->assertCount(1, $events);
        $this->assertSame('0 0 * * 0', $events->first()->expression);
    }
}
