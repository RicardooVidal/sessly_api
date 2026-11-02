<?php

namespace Tests\Http\Controllers\ContractController;

use App\Domains\Contract\Entities\Contract;
use App\Http\Controllers\ContractController;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class IndexTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(now()->startOfSecond());
    }

    public function rota(): string
    {
        return route('contract.index');
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $this->assertEquals(
            $this->rota(),
            URL::action([ContractController::class, 'index'])
        );
    }

    #[
        Test,
        TestDox('Deve listar todos as consultas com sucesso'),
        DataProvider('filtroDataProvider')
    ]
    public function sucesso(array $params): void
    {
        $contratos = $this->criarContratos();

        /** @var Contract $contract */
        $contract = $contratos->first();

        $data = collect(self::mapRecursive($params, compact('contract')));

        if ($data->has(0)) {
            $data = null;
        } else {
            if ($data->has('valid_until')) {
                $contract->valid_until = now()->addMonth();
                $contract->save();
                $data->put('valid_until', $contract->valid_until->format('Y-m-d'));
            }
        }

        $this->login();

        $this
            ->getJson($this->rota() . '?' . http_build_query($data ? $data->toArray() : []))
            ->assertOk()
            ->assertJsonCount($data ? 1 : $contratos->count(), 'data')
            ->assertJsonStructure([
                'data' => [
                    [
                        'uuid',
                        'description',
                        'valid_until',
                        'document_url',
                        'created_at',
                        'updated_at',
                        'patient' => [
                            'uuid',
                            'name',
                        ]
                    ]
                ]
            ]);
    }

    private function criarContratos(): Collection
    {
        collect(range(1, 5))->each(function() {
            Contract::factory()
                ->withPatient()
                ->create();
        });

        return Contract::all();
    }

    public static function filtroDataProvider(): array
    {
        return [
            'sem filtros' => [
                [null]]
            ,
            'filtro por uuid' => [
                ['uuid' => fn(Contract $contract) => $contract->uuid]
            ],
            'filtro por descrição' => [
                ['description' => fn(Contract $contract) => $contract->description]
            ],
            'filtro por data de término do contrato' => [
                ['valid_until' => fn(Contract $contract) => $contract->valid_until]
            ],
            'filtro por uuid do paciente' => [
                ['patient_uuid' => fn(Contract $contract) => $contract->patient->uuid]
            ],
        ];
    }
}
