<?php

namespace Http\Controllers\ContractController;

use App\Domains\Appointment\Entities\Appointment;
use App\Domains\Appointment\Enums\AppointmentTimeEnum;
use App\Domains\Appointment\Enums\AppointmentTypeEnum;
use App\Domains\Contract\Entities\Contract;
use App\Domains\Patient\Entities\Patient;
use App\Exceptions\PatientNotActiveException;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\PatientController;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class StoreTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(now()->startOfSecond());
    }

    public function rota(): string
    {
        return route('contract.store');
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $this->assertEquals(
            $this->rota(),
            URL::action([ContractController::class, 'store'])
        );
    }

    #[
        Test,
        TestDox('Deve cadastrar um contrato com sucesso'),
    ]
    public function sucesso(): void
    {
        Storage::fake('app_files');

        /** @var Patient $patient */
        $patient = Patient::factory()->create();

        $date = now()->addDay();

        $parametros = [
            'patient' => [
                'uuid' => $patient->uuid,
            ],
            'description' => $this->faker->text,
            'valid_until' => $date->format('Y-m-d H:i:s'),
            'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
        ];

        $this->login();

        $this
            ->postJson($this->rota(), $parametros)
            ->assertCreated()
            ->assertJsonFragments([
                'data' => [
                    'description' => $parametros['description'],
                    'patient' => [
                        'uuid' => $patient->uuid,
                        'name' => $patient->name,
                        'phone' => null,
                        'gender' => null,
                        'email' => null,
                        'type' => null,
                        'time' => null,
                        'active' => null,
                        'document' => null,
                        'notes' => null,
                    ],
                    'valid_until' => $date->toW3cString(),
                    'file' => null,
                    'document_url' => Storage::disk('app_files')->url('contracts/contract_patient_' . $patient->uuid . '.pdf'),
                ]
            ]);
    }

    #[
        Test,
        TestDox('Não deve cadastrar caso o tipo de arquivo esteja fora do esperado'),
    ]
    public function erroTipoArquivoInvalido(): void
    {
        Storage::fake('app_files');

        /** @var Patient $patient */

        $patient = Patient::factory()->create();

        $date = now()->addDay();

        $parametros = [
            'patient' => [
                'uuid' => $patient->uuid,
            ],
            'description' => $this->faker->text,
            'valid_until' => $date->format('Y-m-d H:i:s'),
            'file' => UploadedFile::fake()->create('contract.zip'),
        ];

        $this->login();

        $this->postJson($this->rota(), $parametros)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    #[
        Test,
        TestDox('Não deve cadastrar um contrato sem estar logado'),
    ]
    public function erroNaoLogado(): void
    {
        /** @var Patient $patient */
        $patient = Patient::factory()->create();

        $date = now()->addDay();

        $parametros = [
            'patient' => [
                'uuid' => $patient->uuid,
            ],
            'description' => $this->faker->text,
            'valid_until' => $date->format('Y-m-d H:i:s'),
            'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
        ];

        $this
            ->postJson($this->rota(), $parametros)
            ->assertUnauthorized();
    }

    #[Test, TestDox('Não deve permitir inserir um contrato para um paciente que já tem contrato')]
    public function pacienteComContrato(): void
    {
        /** @var Contract $contract */
        $contract = Contract::factory()->withPatient()->create();

        $date = now()->addDay();

        $parametros = [
            'patient' => [
                'uuid' => $contract->patient->uuid,
            ],
            'description' => $this->faker->text,
            'valid_until' => $date->format('Y-m-d H:i:s'),
            'file' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
        ];

        $this->login();

        $this
            ->postJson($this->rota(), $parametros)
            ->assertUnprocessable()
            ->assertInvalid(['patient' => 'Paciente já tem contrato ativo!']);
    }

    #[
        Test,
        TestDox('Deve retornar erro para parâmetros inválidos'),
        DataProvider('parametrosInvalidosDataProvider')
    ]
    public function parametrosInvalidos(array $parametro): void
    {
        $this->login();

        $this
            ->postJson($this->rota(), $parametro)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                array_key_first($parametro),
            ]);
    }

    public static function parametrosInvalidosDataProvider(): array
    {
        return [
            'description inválido' => [
                ['description' => 1]
            ],
            'patient inválido' => [
                ['patient' => null]
            ],
            'valid_until inválido' => [
                ['valid_until'=> 'invalid-date']
            ],
            'valid_until na data atual' => [
                ['valid_until'=> now()]
            ],
            'file inválido' => [
                ['file' => 'invalid-file']
            ],
        ];
    }
}
