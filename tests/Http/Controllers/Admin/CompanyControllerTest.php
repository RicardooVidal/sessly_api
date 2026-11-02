<?php

namespace Tests\Http\Controllers\Admin;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class CompanyControllerTest extends TestCase
{
    #[Test, TestDox('Não deve permitir acesso de usuário que não seja root')]
    public function naoDevePermitirAcessoDeNaoRoot(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->getJson(route('admin.companies.index'))
            ->assertForbidden();
    }

    #[Test, TestDox('Deve listar empresas para o root')]
    public function deveListarEmpresasParaRoot(): void
    {
        $root = User::query()->findOrFail(1);

        $this->actingAs($root)
            ->getJson(route('admin.companies.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'uuid',
                        'company',
                        'name',
                        'email',
                        'document',
                        'until',
                        'active',
                        'user',
                    ],
                ],
            ]);
    }

    #[Test, TestDox('Deve exibir empresa para o root')]
    public function deveExibirEmpresaParaRoot(): void
    {
        $company = Company::factory()->create([
            'company' => 'empresa_exibida',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'exibida@teste.com',
        ]);

        $root = User::query()->findOrFail(1);

        $this->actingAs($root)
            ->getJson(route('admin.companies.show', ['company' => $company->uuid]))
            ->assertOk()
            ->assertJsonPath('data.uuid', $company->uuid)
            ->assertJsonPath('data.company', 'empresa_exibida')
            ->assertJsonPath('data.user.email', $user->email);
    }

    #[Test, TestDox('Deve cadastrar empresa, usuário e executar migrations')]
    public function deveCadastrarEmpresaUsuarioEExecutarMigrations(): void
    {
        $originalKernel = Artisan::getFacadeRoot();
        $kernel = \Mockery::mock($originalKernel);
        $kernel->shouldReceive('call')
            ->once()
            ->with('app:tenant-migrations', ['company' => 'empresa_teste'])
            ->andReturn(0);
        $kernel->shouldReceive('output')->once()->andReturn('Migrations executed successfully.');
        Artisan::swap($kernel);

        $root = User::query()->findOrFail(1);

        $payload = [
            'company' => 'empresa_teste',
            'name' => 'Empresa Teste',
            'email' => 'empresa@teste.com',
            'document' => '12345678901',
            'contract' => 'contrato-1',
            'until' => '2200-01-01',
            'active' => true,
            'user_name' => 'Usuário Teste',
            'user_email' => 'usuario@teste.com',
            'user_password' => 'Senha@123',
            'user_password_confirmation' => 'Senha@123',
        ];

        try {
            $this->actingAs($root)
                ->postJson(route('admin.companies.store'), $payload)
                ->assertCreated()
                ->assertJsonPath('data.company', 'empresa_teste')
                ->assertJsonPath('data.user.email', 'usuario@teste.com');
        } finally {
            Artisan::swap($originalKernel);
        }

        $this->assertDatabaseHas('companies', [
            'company' => 'empresa_teste',
            'document' => '12345678901',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'usuario@teste.com',
        ]);
    }

    #[Test, TestDox('Deve atualizar empresa e usuário vinculado')]
    public function deveAtualizarEmpresaEUsuarioVinculado(): void
    {
        $company = Company::factory()->create([
            'company' => 'empresa_editada',
            'document' => '98765432100',
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'antigo@teste.com',
        ]);

        $root = User::query()->findOrFail(1);

        $payload = [
            'name' => 'Empresa Editada',
            'email' => 'editada@teste.com',
            'document' => '98765432100',
            'contract' => null,
            'until' => '2201-01-01',
            'active' => false,
            'user_name' => 'Usuário Editado',
            'user_email' => 'novo@teste.com',
            'user_password' => '',
            'user_password_confirmation' => '',
        ];

        $this->actingAs($root)
            ->putJson(route('admin.companies.update', ['company' => $company->uuid]), $payload)
            ->assertOk()
            ->assertJsonPath('data.name', 'Empresa Editada')
            ->assertJsonPath('data.user.email', 'novo@teste.com');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Empresa Editada',
            'active' => false,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Usuário Editado',
            'email' => 'novo@teste.com',
        ]);
    }
}
