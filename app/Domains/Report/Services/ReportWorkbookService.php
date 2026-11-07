<?php

namespace App\Domains\Report\Services;

use App\Domains\Report\Entities\Report;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportWorkbookService
{
    private const MONTHS = [
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

    /**
     * @param  array<int, array{patient_uuid: string, patient_name: string, appointments: array<int, array{date: string, effective_value: float, presence: string, notes: ?string}>}>  $patients
     */
    public function build(string $tenant, Report $report, array $patients): string
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('Sessly')
            ->setTitle('Relatório de pagamentos');

        $months = $this->monthsBetween(
            $report->periodStartDate(),
            $report->periodEndDate()
        );

        if ($months === []) {
            $months[] = $report->periodStartDate()->copy();
        }

        foreach ($months as $index => $month) {
            $sheet = $index === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();

            $this->writeMonthSheet($sheet, $month, $patients);
        }

        $relativePath = sprintf('reports/%s/%s.xlsx', $tenant, $report->uuid);

        Storage::disk('local')->makeDirectory(dirname($relativePath));

        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::disk('local')->path($relativePath));

        $spreadsheet->disconnectWorksheets();

        return $relativePath;
    }

    private function writeMonthSheet($sheet, Carbon $month, array $patients): void
    {
        $sheet->setTitle($this->sheetTitle($month));

        $monthPatients = $this->patientsForMonth($patients, $month);

        $sheet->setCellValueExplicit('A1', 'PAGAMENTOS', DataType::TYPE_STRING);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValueExplicit('A2', 'TOTAL', DataType::TYPE_STRING);
        $sheet->getStyle('A2')->getFont()->setBold(true);

        $sheet->setCellValue('B2', $this->total($monthPatients));
        $sheet->getStyle('B2')->getNumberFormat()->setFormatCode('0.00');
        $sheet->getStyle('B2')->getFont()->setBold(true);

        $row = 3;

        if (empty($monthPatients)) {
            $sheet->setCellValueExplicit(
                "A{$row}",
                'Nenhum atendimento no período.',
                DataType::TYPE_STRING
            );
            $sheet->mergeCells("A{$row}:F{$row}");
        }

        foreach ($monthPatients as $patient) {
            $row = $this->writePatientBlock($sheet, $row, $patient);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(18);

        foreach (range('B', 'Z') as $column) {
            $sheet->getColumnDimension($column)->setWidth(14);
        }
    }

    /**
     * @return array<int, Carbon>
     */
    private function monthsBetween(Carbon $start, Carbon $end): array
    {
        $months = [];
        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $months[] = $cursor->copy();
            $cursor->addMonth();
        }

        return $months;
    }

    /**
     * @return array<int, array{patient_uuid: string, patient_name: string, appointments: array<int, array{date: string, effective_value: float, presence: string, notes: ?string}>}>
     */
    private function patientsForMonth(array $patients, Carbon $month): array
    {
        $monthKey = $month->format('Y-m');
        $filtered = [];

        foreach ($patients as $patient) {
            $appointments = array_values(array_filter(
                $patient['appointments'] ?? [],
                fn (array $appointment): bool => Carbon::parse($appointment['date'])->format('Y-m') === $monthKey
            ));

            if ($appointments === []) {
                continue;
            }

            $filtered[] = [
                'patient_uuid' => (string) ($patient['patient_uuid'] ?? ''),
                'patient_name' => (string) ($patient['patient_name'] ?? ''),
                'appointments' => $appointments,
            ];
        }

        return $filtered;
    }

    private function total(array $patients): float
    {
        $total = 0.0;

        foreach ($patients as $patient) {
            foreach ($patient['appointments'] ?? [] as $appointment) {
                $total += (float) ($appointment['effective_value'] ?? 0);
            }
        }

        return $total;
    }

    private function writePatientBlock($sheet, int $startRow, array $patient): int
    {
        $patientName = (string) ($patient['patient_name'] ?? '');
        $appointments = $patient['appointments'] ?? [];

        $sheet->setCellValueExplicit("A{$startRow}", $patientName, DataType::TYPE_STRING);
        $sheet->getStyle("A{$startRow}")->getFont()->setBold(true);

        $datesRow = $startRow + 1;
        $valuesRow = $startRow + 2;
        $paidRow = $startRow + 3;

        foreach (['Data' => $datesRow, 'Valor' => $valuesRow, 'Pago' => $paidRow] as $label => $labelRow) {
            $sheet->setCellValueExplicit("A{$labelRow}", $label, DataType::TYPE_STRING);
            $sheet->getStyle("A{$labelRow}")->getFont()->setBold(true);
        }

        $lastColumnIndex = 1;

        foreach (array_values($appointments) as $index => $appointment) {
            $columnIndex = $index + 2;
            $lastColumnIndex = $columnIndex;
            $column = Coordinate::stringFromColumnIndex($columnIndex);

            $date = Carbon::parse($appointment['date'])->startOfDay();
            $sheet->setCellValue("{$column}{$datesRow}", ExcelDate::PHPToExcel($date));
            $sheet->getStyle("{$column}{$datesRow}")
                ->getNumberFormat()
                ->setFormatCode('dd/mm');

            $effectiveValue = (float) ($appointment['effective_value'] ?? 0);

            if ($effectiveValue > 0) {
                $sheet->setCellValue("{$column}{$valuesRow}", $effectiveValue);
                $sheet->getStyle("{$column}{$valuesRow}")
                    ->getNumberFormat()
                    ->setFormatCode('0.00');
            } else {
                $sheet->setCellValueExplicit("{$column}{$valuesRow}", '/', DataType::TYPE_STRING);
            }

            $sheet->setCellValueExplicit(
                "{$column}{$paidRow}",
                $this->paidLabel($appointment),
                DataType::TYPE_STRING
            );
        }

        $lastColumn = Coordinate::stringFromColumnIndex(max(2, $lastColumnIndex));
        $range = "A{$startRow}:{$lastColumn}{$paidRow}";

        $sheet->getStyle($range)
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        $sheet->getStyle("B{$datesRow}:{$lastColumn}{$paidRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("B{$paidRow}:{$lastColumn}{$paidRow}")
            ->getAlignment()
            ->setWrapText(true);

        $sheet->getRowDimension($paidRow)->setRowHeight(30);

        return $paidRow;
    }

    private function paidLabel(array $appointment): string
    {
        $presence = $appointment['presence'] ?? 'pending';
        $effectiveValue = (float) ($appointment['effective_value'] ?? 0);

        $label = match (true) {
            $presence === 'present' && $effectiveValue <= 0 => 'QUINZENAL',
            $presence === 'present' => 'OK',
            $presence === 'present_to_receive' => 'A RECEBER',
            $presence === 'absent' => 'FALTA',
            default => 'PENDENTE',
        };

        $notes = trim((string) ($appointment['notes'] ?? ''));

        return $notes !== '' ? "{$label} - {$notes}" : $label;
    }

    private function sheetTitle(Carbon $referenceMonth): string
    {
        $month = (int) $referenceMonth->month;

        return sprintf('%s.%s', self::MONTHS[$month], $referenceMonth->format('y'));
    }
}
