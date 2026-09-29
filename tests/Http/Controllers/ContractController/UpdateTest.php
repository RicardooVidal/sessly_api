<?php

namespace Http\Controllers\ContractController;

use App\Domains\Contract\Entities\Contract;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    #[Test, TestDox('Não deve disponibilizar a rota de atualização de contrato')]
    public function rotaIndisponivel(): void
    {
        $this->assertFalse(Route::has('contract.update'));
    }

    #[Test, TestDox('Não deve permitir alterar o contrato pela API')]
    public function erroAtualizacao(): void
    {
        $this->login();
        $contract = Contract::factory()->withPatient()->create();
        $description = $contract->description;

        $this->putJson('/api/contract/'.$contract->uuid, ['description' => 'Alteração não permitida'])
            ->assertStatus(405);

        $this->assertSame($description, $contract->fresh()->description);
    }
}
