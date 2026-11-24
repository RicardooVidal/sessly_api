<?php

namespace Tests\Domains\Patient\Services;

use App\Domains\Patient\Entities\MedicalRecord;
use App\Domains\Patient\Entities\Patient;
use App\Domains\Patient\Services\MedicalRecordService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Exception\Exception;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class MedicalRecordServiceTest extends TestCase
{
    #[Test, TestDox('Deve retornar nulo quando o paciente não possui prontuário')]
    public function semProntuario(): void
    {
        Storage::fake();
        $patient = Patient::factory()->create();
        $this->assertNull(app(MedicalRecordService::class)->getPatientMedicalRecordEncoded($patient->uuid));
    }

    /**
     * @throws Exception
     */
    #[Test, TestDox('Deve codificar o prontuário criptografado preservando acentos e caracteres especiais')]
    public function sucessoCodificacao(): void
    {
        Storage::fake();
        $patient = Patient::factory()->create();
        $html = '<p>Evolução: atenção e saúde 📝</p>';
        MedicalRecord::query()->factory()->withPatient($patient)->create(['record' => $html]);

        $this->assertSame(base64_encode($html), app(MedicalRecordService::class)->getPatientMedicalRecordEncoded($patient->uuid));
    }

    /**
     * @throws Exception
     */
    #[Test, TestDox('Deve manter compatibilidade com prontuário HTML legado no storage')]
    public function sucessoCodificacaoLegado(): void
    {
        Storage::fake();
        $patient = Patient::factory()->create();
        $html = '<p>Prontuário legado</p>';
        Storage::put("medical_records/{$patient->uuid}.html", $html);

        $this->assertSame(base64_encode($html), app(MedicalRecordService::class)->getPatientMedicalRecordEncoded($patient->uuid));
    }

    /**
     * @throws Exception
     */
    #[Test, TestDox('Deve criptografar o prontuário no banco e manter o DOCX atualizado sem duplicar registros')]
    public function sucessoAtualizacao(): void
    {
        Storage::fake();
        $patient = Patient::factory()->create();
        $other = Patient::factory()->create();
        $service = app(MedicalRecordService::class);

        Storage::put("medical_records/{$patient->uuid}.html", '<p>HTML legado</p>');
        $service->updatePatientMedicalRecord($patient->uuid, base64_encode('<p>Primeira sessão</p>'));

        $record = MedicalRecord::query()->where('patient_id', $patient->id)->sole();
        $this->assertSame("medical_records/{$patient->uuid}.docx", $record->document);
        $this->assertSame('<p>Primeira sessão</p>', $record->record);
        Storage::assertExists([$record->document]);
        Storage::assertMissing("medical_records/{$patient->uuid}.html");

        $rawRecord = DB::table('medical_records')->where('id', $record->id)->value('record');
        $this->assertNotSame('<p>Primeira sessão</p>', $rawRecord);
        $this->assertStringNotContainsString('<p>', $rawRecord);
        $this->assertSame('<p>Primeira sessão</p>', Crypt::decryptString($rawRecord));

        $firstDocumentHash = hash_file('sha256', Storage::path($record->document));

        $service->updatePatientMedicalRecord($other->uuid, base64_encode('<p>Outro paciente</p>'));
        $service->updatePatientMedicalRecord($patient->uuid, base64_encode('<p>Sessão atualizada</p>'));

        $this->assertSame($record->id, MedicalRecord::query()->where('patient_id', $patient->id)->sole()->id);
        $this->assertSame('<p>Sessão atualizada</p>', $record->fresh()->record);
        $this->assertNotSame($firstDocumentHash, hash_file('sha256', Storage::path($record->document)));
        $this->assertSame(base64_encode('<p>Sessão atualizada</p>'), $service->getPatientMedicalRecordEncoded($patient->uuid));
        $this->assertSame(base64_encode('<p>Outro paciente</p>'), $service->getPatientMedicalRecordEncoded($other->uuid));
        $this->assertDatabaseCount('medical_records', 2);
    }

    /**
     * @throws Exception
     */
    #[Test, TestDox('Deve limpar prontuário e remover DOCX quando o conteúdo ficar vazio')]
    public function sucessoLimpeza(): void
    {
        Storage::fake();
        $patient = Patient::factory()->create();
        $service = app(MedicalRecordService::class);

        $service->updatePatientMedicalRecord($patient->uuid, base64_encode('<p>Conteúdo antigo</p>'));
        $record = MedicalRecord::query()->where('patient_id', $patient->id)->sole();
        Storage::put("medical_records/{$patient->uuid}.html", '<p>HTML legado</p>');

        $service->updatePatientMedicalRecord($patient->uuid, base64_encode(''));

        $record->refresh();
        $this->assertNull($record->record);
        $this->assertNull($record->document);
        Storage::assertMissing([
            "medical_records/{$patient->uuid}.docx",
            "medical_records/{$patient->uuid}.html",
        ]);
        $this->assertNull($service->getPatientMedicalRecordEncoded($patient->uuid));
    }
}
