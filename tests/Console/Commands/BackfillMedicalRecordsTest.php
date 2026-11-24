<?php

namespace Tests\Console\Commands;

use App\Domains\Patient\Entities\MedicalRecord;
use App\Domains\Patient\Entities\Patient;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class BackfillMedicalRecordsTest extends TestCase
{
    #[Test, TestDox('Deve migrar o HTML legado para o banco criptografado e remover o arquivo')]
    public function migraHtmlLegado(): void
    {
        Storage::fake();

        $patient = Patient::factory()->create();
        $html = '<p>Prontuário legado</p>';
        MedicalRecord::factory()->withPatient($patient)->create([
            'record' => null,
            'document' => null,
        ]);
        Storage::put("medical_records/{$patient->uuid}.html", $html);

        $this->artisan('app:backfill-medical-records', [
            'tenant' => 'test',
            '--delete-html' => true,
        ])->assertSuccessful();

        $record = MedicalRecord::query()->where('patient_id', $patient->id)->sole();
        $rawRecord = DB::table('medical_records')->where('id', $record->id)->value('record');

        $this->assertSame($html, $record->record);
        $this->assertStringNotContainsString('<p>', $rawRecord);
        $this->assertSame($html, Crypt::decryptString($rawRecord));
        Storage::assertExists([$record->document]);
        Storage::assertMissing("medical_records/{$patient->uuid}.html");
    }
}
