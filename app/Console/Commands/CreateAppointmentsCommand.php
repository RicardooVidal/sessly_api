<?php

namespace App\Console\Commands;

use App\Domains\Appointment\Jobs\CreateAppointmentJob;
use App\Domains\Appointment\Repositories\AppointmentRepository;
use App\Domains\Patient\Entities\Patient;
use App\Helpers\DatabaseHelper;
use App\Models\Company;
use Dflydev\DotAccessData\Data;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Bus;
use Tests\Console\Commands\CreateAppointmentsCommandTest;

/**
 * @see CreateAppointmentsCommandTest
 */
class CreateAppointmentsCommand extends Command
{
    protected $signature = 'app:create-appointments';

    protected $description = 'Despacha a criacao automatica de consultas dos pacientes';

    public function handle(AppointmentRepository $appointmentRepository): int
    {
        if (! config('commands.appointments.enabled')) {
            $this->info('Fila não habilitada');
            return self::SUCCESS;
        }

        $dispatched = false;

        $tenants = Company::query()
            ->where('active', true)
            ->pluck('name', 'company');

        $tenants->each(function (string $tenant, string $companyAlias) use (&$dispatched, $appointmentRepository) {
            $this->info("Verificando criação automática de consultas para o tenant {$tenant}");
            DatabaseHelper::changeSchema(schema: $companyAlias);

            Patient::query()
                ->where('create_appointments', true)
                ->whereHas('appointments')
                ->chunkById(
                    config('commands.appointments.batch'),
                    function (Collection $patients) use (&$dispatched, $companyAlias, $appointmentRepository) {
                        $jobs = $patients->map(fn (Patient $patient) => new CreateAppointmentJob(
                            $companyAlias,
                            $patient,
                            $appointmentRepository)
                        );

                        if ($jobs->isNotEmpty()) {
                            DatabaseHelper::changeSchema();
                            $dispatched = true;

                            Bus::batch($jobs)
                                ->onQueue(config('commands.appointments.queue'))
                                ->dispatch();
                        }
                    });
        });

        if ($dispatched) {
            $this->info('Job para criar consultas disparado com sucesso');
            return self::SUCCESS;
        }

        $this->info('Nenhum paciente elegível encontrado');

        return self::SUCCESS;
    }
}
