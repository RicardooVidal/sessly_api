<?php

namespace Http\Controllers\AuthController;

use App\Http\Controllers\AuthController;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

class CompaniesTest extends TestCase
{
    use WithFaker;

    public function rota(string $uuid): string
    {
        return route('companies', ['user' => $uuid]);
    }

    #[Test, TestDox('Deve retornar a rota correta')]
    public function rotaCorreta(): void
    {
        $uuid = $this->faker->uuid;

        $this->assertEquals(
            $this->rota($uuid),
            URL::action([AuthController::class, 'companies'], ['user' => $uuid])
        );
    }

    #[Test, TestDox('Deve retornar somente empresas no qual o usuário pertence')]
    public function sucesso(): void
    {
        $otherCompany = Company::factory()->create();
        $user = User::factory()->withCompany()->create();

        $this->actingAs($user)
            ->getJson($this->rota($user->uuid))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $user->company->uuid)
            ->assertJsonMissing(['uuid' => $otherCompany->uuid])
            ->assertJsonStructure([
                'data' => [
                    [
                        'uuid',
                        'company',
                    ],
                ],
            ]);
    }

    #[Test, TestDox('Deve retornar todas as empresas para o root')]
    public function rootDeveRetornarTodasEmpresas(): void
    {
        Company::factory()->create();

        $root = User::query()->findOrFail(1);

        $this->actingAs($root)
            ->getJson($this->rota($root->uuid))
            ->assertOk()
            ->assertJsonCount(Company::query()->count(), 'data');
    }
}
