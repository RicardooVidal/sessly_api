<?php

namespace App\Console\Commands;

use App\Domains\Patient\Entities\MedicalRecord;
use App\Domains\Patient\Services\MedicalRecordService;
use App\Helpers\DatabaseHelper;
use App\Services\PhpWordService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class BackfillMedicalRecords extends Command
{
    protected $signature = 'app:backfill-medical-records {tenant} {--delete-html : Remove os arquivos HTML após migrar}';

    protected $description = 'Migra os prontuários em HTML para a coluna record criptografada e garante o DOCX';

    public function handle(
        MedicalRecordService $medicalRecordService,
        PhpWordService $phpWordService,
    ): int {
        $tenant = (string) $this->argument('tenant');

        DatabaseHelper::changeSchema(schema: $tenant);

        try {
            $records = MedicalRecord::query()
                ->with(['patient' => fn ($query) => $query->withTrashed()])
                ->whereNotNull('patient_id')
                ->get();

            $migrated = 0;
            $encrypted = 0;
            $docxGenerated = 0;
            $htmlDeleted = 0;
            $skipped = 0;

            foreach ($records as $record) {
                $patient = $record->patient;
                $patientUuid = $patient?->uuid;

                if (! $patient || ! $patientUuid) {
                    $skipped++;

                    continue;
                }

                $htmlPath = "medical_records/{$patientUuid}.html";
                $rawRecord = $record->getRawOriginal('record');
                $documentExists = $record->document && Storage::exists($record->document);
                $htmlExists = Storage::exists($htmlPath);

                if ($rawRecord !== null && $rawRecord !== '') {
                    try {
                        $html = Crypt::decryptString($rawRecord);
                    } catch (DecryptException) {
                        $html = $rawRecord;
                        $record->record = $html;
                        $record->save();
                        $encrypted++;
                    }

                    if (! $documentExists && ! $this->isBlankHtml($html)) {
                        $medicalRecordService->updateMedicalRecord(
                            $patient,
                            base64_encode($html),
                            (bool) $this->option('delete-html')
                        );
                        $docxGenerated++;
                    }
                } else {
                    $html = $htmlExists ? Storage::get($htmlPath) : null;

                    if ($this->isBlankHtml($html) && $documentExists) {
                        $html = $phpWordService
                            ->setFilePath(Storage::path($record->document))
                            ->convertToHtml()
                            ->extractBody()
                            ->getContent();
                    }

                    if (! $this->isBlankHtml($html)) {
                        $medicalRecordService->updateMedicalRecord(
                            $patient,
                            base64_encode($html),
                            (bool) $this->option('delete-html')
                        );
                        $migrated++;
                        $docxGenerated++;
                    } else {
                        $skipped++;
                    }
                }

                if ($this->option('delete-html') && $htmlExists) {
                    Storage::delete($htmlPath);
                    $htmlDeleted++;
                }
            }
            $this->info("Tenant: {$tenant}");
            $this->info("Migrados do HTML: {$migrated}");
            $this->info("Registros criptografados: {$encrypted}");
            $this->info("DOCX gerados/atualizados: {$docxGenerated}");
            $this->info("HTML removidos: {$htmlDeleted}");
            $this->info("Ignorados: {$skipped}");

            return self::SUCCESS;
        } finally {
            DatabaseHelper::changeSchema();
        }
    }

    private function isBlankHtml(?string $html): bool
    {
        return $html === null || trim(strip_tags($html)) === '';
    }
}
