<?php

namespace Tests\Http\Controllers\MedicalRecordController;

use App\Domains\Patient\Entities\Patient;
use App\Http\Controllers\MedicalRecordController;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class ShowTest extends TestCase
{
    public function rota(string $uuid): string
    {
        return route('medical-record.get-by-patient', ['uuid' => $uuid]);
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $uuid = $this->faker->uuid;

        $this->assertEquals(
            $this->rota($uuid),
            URL::action([MedicalRecordController::class, 'getMedicalRecord'], ['uuid' => $uuid])
        );
    }

    #[Test, TestDox('Deve retornar o prontuário codificado preservando caracteres especiais')]
    public function sucesso(): void
    {
        Storage::fake();
        $patient = Patient::factory()->create();
        $html = '<p>Anotações da sessão 📝</p>';
        Storage::put("medical_records/{$patient->uuid}.html", $html);

        $this->login();

        $this->getJson($this->rota($patient->uuid))
            ->assertOk()
            ->assertExactJson(['content' => base64_encode($html)]);
    }

    #[Test, TestDox('Deve retornar nulo quando o paciente não possui prontuário')]
    public function semProntuario(): void
    {
        Storage::fake();
        $patient = Patient::factory()->create();

        $this->login();

        $this->getJson($this->rota($patient->uuid))
            ->assertOk()
            ->assertExactJson(['content' => null]);
    }

    #[Test, TestDox('Não deve exibir prontuário sem estar logado')]
    public function erroNaoLogado(): void
    {
        $this->getJson($this->rota($this->faker->uuid))
            ->assertUnauthorized();
    }
}
