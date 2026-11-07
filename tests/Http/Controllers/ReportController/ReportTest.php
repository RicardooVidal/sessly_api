<?php

namespace Http\Controllers\ReportController;

use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Patient\Entities\Patient;
use App\Domains\Report\Entities\Report;
use App\Domains\Report\Enums\ReportStatusEnum;
use App\Domains\Report\Jobs\GenerateReportChunkJob;
use App\Models\Company;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ReportTest extends TestCase
{
    #[Test, TestDox('Deve iniciar a geração do relatório em lote')]
    public function iniciaRelatorioEmLote(): void
    {
        Bus::fake();
        Storage::fake('local');

        $referenceMonth = now()->startOfMonth();
        $patient = Patient::factory()->create();
        Appointment::factory()->withPatient($patient)->create([
            'date' => $referenceMonth->copy()->addDays(9)->setTime(9, 0),
        ]);

        $company = $this->loginWithTenant();

        $this->withHeader('X-Tenant', $company->uuid)
            ->postJson(route('reports.store'), [
                'month' => $referenceMonth->format('Y-m'),
            ])
            ->assertAccepted()
            ->assertJsonPath('data.status', ReportStatusEnum::QUEUED->value)
            ->assertJsonPath('data.total_patients', 1)
            ->assertJsonPath('data.progress', 0);

        $this->assertDatabaseHas('reports', [
            'reference_month' => $referenceMonth->toDateTimeString(),
            'status' => ReportStatusEnum::QUEUED->value,
            'total_patients' => 1,
        ]);

        Bus::assertBatchCount(1);
        Bus::assertBatched(function (PendingBatch $batch) {
            return $batch->jobs->count() === 1
                && $batch->jobs->first() instanceof GenerateReportChunkJob;
        });
    }

    #[Test, TestDox('Deve validar mês e paciente do relatório')]
    public function validaFiltros(): void
    {
        Storage::fake('local');
        $company = $this->loginWithTenant();

        $this->withHeader('X-Tenant', $company->uuid)
            ->postJson(route('reports.store'), [
                'month' => '2026-13',
                'patient_uuid' => '00000000-0000-0000-0000-000000000000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['month', 'patient_uuid']);
    }

    #[Test, TestDox('Deve retornar status, progresso e link temporário de download')]
    public function retornaStatusEDownload(): void
    {
        Storage::fake('local');

        $referenceMonth = now()->startOfMonth();
        $company = $this->loginWithTenant();
        $filePath = 'reports/'.$company->company.'/relatorio.xlsx';
        Storage::disk('local')->put($filePath, 'conteudo');

        $report = Report::query()->create([
            'reference_month' => $referenceMonth,
            'status' => ReportStatusEnum::COMPLETED,
            'total_patients' => 2,
            'processed_patients' => 2,
            'file_path' => $filePath,
            'completed_at' => now(),
        ]);

        $response = $this->withHeader('X-Tenant', $company->uuid)
            ->getJson(route('reports.show', ['report' => $report->uuid]))
            ->assertOk()
            ->assertJsonPath('data.status', ReportStatusEnum::COMPLETED->value)
            ->assertJsonPath('data.progress', 100)
            ->assertJsonPath('data.status_description', 'Concluído');

        $downloadUrl = $response->json('data.download_url');

        $this->assertNotNull($downloadUrl);

        $this->get($downloadUrl)
            ->assertOk()
            ->assertDownload('relatorio-'.$referenceMonth->format('Y-m').'.xlsx');
    }

    #[Test, TestDox('Deve cancelar um relatório em andamento')]
    public function cancelaRelatorio(): void
    {
        Storage::fake('local');

        $company = $this->loginWithTenant();

        $report = Report::query()->create([
            'reference_month' => now()->startOfMonth(),
            'status' => ReportStatusEnum::PROCESSING,
            'total_patients' => 2,
            'processed_patients' => 1,
        ]);

        $this->withHeader('X-Tenant', $company->uuid)
            ->postJson(route('reports.cancel', ['report' => $report->uuid]))
            ->assertOk()
            ->assertJsonPath('data.status', ReportStatusEnum::CANCELLED->value);

        $this->assertDatabaseHas('reports', [
            'uuid' => $report->uuid,
            'status' => ReportStatusEnum::CANCELLED->value,
        ]);
    }

    #[Test, TestDox('Deve gerar o arquivo XLSX quando a fila for sincrona')]
    public function geraArquivoXlsx(): void
    {
        Storage::fake('local');

        $referenceMonth = now()->startOfMonth();
        $patient = Patient::factory()->create([
            'name' => 'Paciente Teste',
            'value' => 200.0,
        ]);

        Appointment::factory()->withPatient($patient)->create([
            'date' => $referenceMonth->copy()->addDays(2)->setTime(9, 0),
            'presence' => 'present',
            'value' => 50.0,
            'notes' => null,
        ]);
        Appointment::factory()->withPatient($patient)->create([
            'date' => $referenceMonth->copy()->addDays(9)->setTime(9, 0),
            'presence' => 'present',
            'value' => 0.0,
            'notes' => null,
        ]);

        $company = $this->loginWithTenant();

        $response = $this->withHeader('X-Tenant', $company->uuid)
            ->postJson(route('reports.store'), [
                'month' => $referenceMonth->format('Y-m'),
            ])
            ->assertAccepted()
            ->assertJsonPath('data.status', ReportStatusEnum::COMPLETED->value);

        $report = Report::query()->where('uuid', $response->json('data.uuid'))->firstOrFail();

        $this->assertNotNull($report->file_path);
        Storage::disk('local')->assertExists($report->file_path);
    }

    #[Test, TestDox('Deve iniciar relatório por período normalizando meses completos')]
    public function iniciaRelatorioPorPeriodo(): void
    {
        Bus::fake();
        Storage::fake('local');

        $periodStart = now()->startOfMonth();
        $periodEnd = $periodStart->copy()->addMonths(2)->endOfMonth();
        $patient = Patient::factory()->create();
        Appointment::factory()->withPatient($patient)->create([
            'date' => $periodStart->copy()->addMonth()->addDays(9)->setTime(9, 0),
        ]);

        $company = $this->loginWithTenant();

        $this->withHeader('X-Tenant', $company->uuid)
            ->postJson(route('reports.store'), [
                'start_date' => $periodStart->copy()->addDays(4)->toDateString(),
                'end_date' => $periodEnd->copy()->subDays(11)->toDateString(),
            ])
            ->assertAccepted()
            ->assertJsonPath('data.period_start', $periodStart->toDateString())
            ->assertJsonPath('data.period_end', $periodEnd->toDateString())
            ->assertJsonPath('data.period_label', $periodStart->format('d/m/Y').' a '.$periodEnd->format('d/m/Y'))
            ->assertJsonPath('data.total_patients', 1);

        $this->assertDatabaseHas('reports', [
            'reference_month' => $periodStart->toDateTimeString(),
            'period_start' => $periodStart->toDateTimeString(),
            'period_end' => $periodEnd->copy()->startOfDay()->toDateTimeString(),
            'status' => ReportStatusEnum::QUEUED->value,
        ]);

        Bus::assertBatchCount(1);
    }

    #[Test, TestDox('Deve validar período incompleto, invertido e misturado com mês')]
    public function validaPeriodo(): void
    {
        Storage::fake('local');
        $company = $this->loginWithTenant();

        $laterMonth = now()->addMonth()->startOfMonth();
        $earlierMonth = now()->startOfMonth();

        $this->withHeader('X-Tenant', $company->uuid)
            ->postJson(route('reports.store'), [
                'start_date' => $laterMonth->toDateString(),
                'end_date' => $earlierMonth->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);

        $currentMonth = now()->startOfMonth();

        $this->withHeader('X-Tenant', $company->uuid)
            ->postJson(route('reports.store'), [
                'month' => $currentMonth->format('Y-m'),
                'start_date' => $currentMonth->copy()->startOfMonth()->toDateString(),
                'end_date' => $currentMonth->copy()->endOfMonth()->toDateString(),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['month']);
    }

    #[Test, TestDox('Deve gerar o arquivo XLSX de período com uma aba por mês')]
    public function geraArquivoXlsxPeriodo(): void
    {
        Storage::fake('local');

        $periodStart = now()->startOfMonth();
        $periodEnd = $periodStart->copy()->addMonths(2)->endOfMonth();
        $patient = Patient::factory()->create([
            'name' => 'Paciente Teste',
            'value' => 200.0,
        ]);

        Appointment::factory()->withPatient($patient)->create([
            'date' => $periodStart->copy()->addDays(9)->setTime(9, 0),
            'presence' => 'present',
            'value' => 50.0,
        ]);
        Appointment::factory()->withPatient($patient)->create([
            'date' => $periodStart->copy()->addMonths(2)->addDays(9)->setTime(9, 0),
            'presence' => 'present',
            'value' => 75.0,
        ]);

        $company = $this->loginWithTenant();

        $response = $this->withHeader('X-Tenant', $company->uuid)
            ->postJson(route('reports.store'), [
                'start_date' => $periodStart->copy()->addDays(4)->toDateString(),
                'end_date' => $periodEnd->copy()->subDays(11)->toDateString(),
            ])
            ->assertAccepted()
            ->assertJsonPath('data.status', ReportStatusEnum::COMPLETED->value);

        $report = Report::query()->where('uuid', $response->json('data.uuid'))->firstOrFail();

        $this->assertNotNull($report->file_path);
        Storage::disk('local')->assertExists($report->file_path);

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($report->file_path));
        $expectedTitles = array_map(
            fn (int $offset) => $this->monthSheetName($periodStart->copy()->addMonths($offset)),
            [0, 1, 2]
        );

        $this->assertSame(
            $expectedTitles,
            array_map(fn ($sheet) => $sheet->getTitle(), $spreadsheet->getAllSheets())
        );
    }

    #[Test, TestDox('Deve relacionar o paciente por ID sem expor o ID na API')]
    public function relacionaPacientePorId(): void
    {
        Bus::fake();
        Storage::fake('local');

        $referenceMonth = now()->startOfMonth();
        $selectedPatient = Patient::factory()->create();
        $otherPatient = Patient::factory()->create();

        foreach ([$selectedPatient, $otherPatient] as $patient) {
            Appointment::factory()->withPatient($patient)->create([
                'date' => $referenceMonth->copy()->addDays(9)->setTime(9, 0),
            ]);
        }

        $company = $this->loginWithTenant();

        $response = $this->withHeader('X-Tenant', $company->uuid)
            ->postJson(route('reports.store'), [
                'month' => $referenceMonth->format('Y-m'),
                'patient_uuid' => $selectedPatient->uuid,
            ])
            ->assertAccepted()
            ->assertJsonPath('data.total_patients', 1)
            ->assertJsonMissingPath('data.patient_id')
            ->assertJsonMissingPath('data.patientId')
            ->assertJsonMissingPath('data.patient_uuid');

        $report = Report::query()->where('uuid', $response->json('data.uuid'))->firstOrFail();

        $this->assertSame($selectedPatient->id, $report->patient_id);
    }

    private function monthSheetName(Carbon $month): string
    {
        $months = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];

        return $months[(int) $month->month].'.'.$month->format('y');
    }

    private function loginWithTenant(): Company
    {
        $company = $this->login()->company;
        $company->update(['company' => 'tenant_test']);

        return $company->refresh();
    }
}
