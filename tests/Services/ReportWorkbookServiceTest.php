<?php

namespace Tests\Services;

use App\Domains\Report\Entities\Report;
use App\Domains\Report\Enums\ReportStatusEnum;
use App\Domains\Report\Services\ReportWorkbookService;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ReportWorkbookServiceTest extends TestCase
{
    #[Test, TestDox('Deve montar a planilha com os status de pagamento')]
    public function montaPlanilha(): void
    {
        Storage::fake('local');

        $report = Report::query()->create([
            'reference_month' => '2026-01-01',
            'status' => ReportStatusEnum::PROCESSING,
            'total_patients' => 1,
        ]);

        $path = app(ReportWorkbookService::class)->build('tenant_test', $report, [
            [
                'patient_uuid' => 'patient-uuid',
                'patient_name' => 'Paciente Teste',
                'appointments' => [
                    [
                        'date' => '2026-01-05',
                        'effective_value' => 50.0,
                        'presence' => 'present',
                        'notes' => null,
                    ],
                    [
                        'date' => '2026-01-12',
                        'effective_value' => 0.0,
                        'presence' => 'present',
                        'notes' => null,
                    ],
                    [
                        'date' => '2026-01-19',
                        'effective_value' => 80.0,
                        'presence' => 'present_to_receive',
                        'notes' => null,
                    ],
                    [
                        'date' => '2026-01-20',
                        'effective_value' => 30.0,
                        'presence' => 'absent',
                        'notes' => null,
                    ],
                    [
                        'date' => '2026-01-26',
                        'effective_value' => 30.0,
                        'presence' => 'pending',
                        'notes' => null,
                    ],
                    [
                        'date' => '2026-01-27',
                        'effective_value' => 0.0,
                        'presence' => 'present',
                        'notes' => 'Sessão incluída',
                    ],
                ],
            ],
        ]);

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('Janeiro.26', $sheet->getTitle());
        $this->assertSame('PAGAMENTOS', $sheet->getCell('A1')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('A2')->getValue());
        $this->assertSame('190.00', $sheet->getCell('B2')->getFormattedValue());
        $this->assertSame('Paciente Teste', $sheet->getCell('A3')->getValue());
        $this->assertSame('Data', $sheet->getCell('A4')->getValue());
        $this->assertSame('Valor', $sheet->getCell('A5')->getValue());
        $this->assertSame('Pago', $sheet->getCell('A6')->getValue());
        $this->assertSame('05/01', $sheet->getCell('B4')->getFormattedValue());
        $this->assertSame(50.0, (float) $sheet->getCell('B5')->getValue());
        $this->assertSame('/', $sheet->getCell('C5')->getValue());
        $this->assertSame('OK', $sheet->getCell('B6')->getValue());
        $this->assertSame('QUINZENAL', $sheet->getCell('C6')->getValue());
        $this->assertSame('A RECEBER', $sheet->getCell('D6')->getValue());
        $this->assertSame('FALTA', $sheet->getCell('E6')->getValue());
        $this->assertSame('PENDENTE', $sheet->getCell('F6')->getValue());
        $this->assertSame('QUINZENAL - Sessão incluída', $sheet->getCell('G6')->getValue());
    }

    #[Test, TestDox('Deve gerar planilha vazia com mensagem quando não houver pacientes')]
    public function planilhaVazia(): void
    {
        Storage::fake('local');

        $report = Report::query()->create([
            'reference_month' => '2026-02-01',
            'status' => ReportStatusEnum::PROCESSING,
            'total_patients' => 0,
        ]);

        $path = app(ReportWorkbookService::class)->build('tenant_test', $report, []);
        $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));

        $this->assertSame('Fevereiro.26', $spreadsheet->getActiveSheet()->getTitle());
        $this->assertSame('TOTAL', $spreadsheet->getActiveSheet()->getCell('A2')->getValue());
        $this->assertSame('0.00', $spreadsheet->getActiveSheet()->getCell('B2')->getFormattedValue());
        $this->assertSame(
            'Nenhum atendimento no período.',
            $spreadsheet->getActiveSheet()->getCell('A3')->getValue()
        );
    }

    #[Test, TestDox('Deve gerar uma aba por mês com totais separados no período')]
    public function montaPlanilhaPorPeriodo(): void
    {
        Storage::fake('local');

        $report = Report::query()->create([
            'reference_month' => '2026-01-01',
            'period_start' => '2026-01-01',
            'period_end' => '2026-03-31',
            'status' => ReportStatusEnum::PROCESSING,
            'total_patients' => 1,
        ]);

        $path = app(ReportWorkbookService::class)->build('tenant_test', $report, [
            [
                'patient_uuid' => 'patient-uuid',
                'patient_name' => 'Paciente Teste',
                'appointments' => [
                    [
                        'date' => '2026-01-05',
                        'effective_value' => 50.0,
                        'presence' => 'present',
                        'notes' => null,
                    ],
                    [
                        'date' => '2026-03-10',
                        'effective_value' => 75.0,
                        'presence' => 'present',
                        'notes' => null,
                    ],
                ],
            ],
        ]);

        $spreadsheet = IOFactory::load(Storage::disk('local')->path($path));

        $this->assertSame(
            ['Janeiro.26', 'Fevereiro.26', 'Março.26'],
            array_map(fn ($sheet) => $sheet->getTitle(), $spreadsheet->getAllSheets())
        );

        $january = $spreadsheet->getSheetByName('Janeiro.26');
        $february = $spreadsheet->getSheetByName('Fevereiro.26');
        $march = $spreadsheet->getSheetByName('Março.26');

        $this->assertSame('50.00', $january->getCell('B2')->getFormattedValue());
        $this->assertSame('0.00', $february->getCell('B2')->getFormattedValue());
        $this->assertSame('75.00', $march->getCell('B2')->getFormattedValue());
        $this->assertSame('Paciente Teste', $january->getCell('A3')->getValue());
        $this->assertSame('Paciente Teste', $march->getCell('A3')->getValue());
        $this->assertSame(
            'Nenhum atendimento no período.',
            $february->getCell('A3')->getValue()
        );
    }
}
