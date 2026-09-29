<?php

namespace Tests\Services;

use App\Domains\Patient\Entities\MedicalRecord;
use App\Domains\Patient\Entities\Patient;
use App\Domains\Patient\Services\MedicalRecordService;
use Illuminate\Support\Facades\Storage;
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

    #[Test, TestDox('Deve codificar o prontuário preservando acentos e caracteres especiais')]
    public function sucessoCodificacao(): void
    {
        Storage::fake();
        $patient = Patient::factory()->create();
        $html = '<p>Evolução: atenção e saúde 📝</p>';
        Storage::put("medical_records/{$patient->uuid}.html", $html);
        $this->assertSame(base64_encode($html), app(MedicalRecordService::class)->getPatientMedicalRecordEncoded($patient->uuid));
    }

    #[Test, TestDox('Deve atualizar arquivos e registro sem duplicar ou alterar o prontuário de outro paciente')]
    public function sucessoAtualizacao(): void
    {
        Storage::fake();
        $patient = Patient::factory()->create();
        $other = Patient::factory()->create();
        $service = app(MedicalRecordService::class);
        $service->updatePatientMedicalRecord($patient->uuid, base64_encode('<p>Primeira sessão</p>'));
        $record = MedicalRecord::where('patient_id', $patient->id)->sole();
        $this->assertSame("medical_records/{$patient->uuid}.docx", $record->document);
        Storage::assertExists([$record->document, "medical_records/{$patient->uuid}.html"]);
        $this->assertSame('PK', substr(Storage::get($record->document), 0, 2));
        $service->updatePatientMedicalRecord($other->uuid, base64_encode('<p>Outro paciente</p>'));
        $service->updatePatientMedicalRecord($patient->uuid, base64_encode('<p>Sessão atualizada</p>'));
        $this->assertSame($record->id, MedicalRecord::where('patient_id', $patient->id)->sole()->id);
        $this->assertSame('<p>Sessão atualizada</p>', base64_decode($service->getPatientMedicalRecordEncoded($patient->uuid)));
        $this->assertSame('<p>Outro paciente</p>', base64_decode($service->getPatientMedicalRecordEncoded($other->uuid)));
        $this->assertDatabaseCount('medical_records', 2);
    }
}
