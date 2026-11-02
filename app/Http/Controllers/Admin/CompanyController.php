<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\CompanyData;
use App\Data\Admin\StoreCompanyData;
use App\Data\Admin\UpdateCompanyData;
use App\Helpers\DatabaseHelper;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Throwable;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        $companies = Company::query()
            ->with(['users' => fn ($query) => $query->select('id', 'name', 'email', 'company_id')])
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $companies->map(fn (Company $company) => CompanyData::fromModel($company))->values(),
        ]);
    }

    public function show(Company $company): CompanyData
    {
        return CompanyData::fromModel($company)->wrap('data');
    }

    public function store(StoreCompanyData $data): JsonResponse
    {
        $output = '';

        try {
            $company = DB::transaction(function () use ($data, &$output) {
                $company = Company::query()->create([
                    'company' => $data->company,
                    'name' => $data->name,
                    'email' => $data->email,
                    'document' => $data->document,
                    'contract' => $data->contract,
                    'until' => $data->until,
                    'active' => $data->active,
                ]);

                $company->users()->create([
                    'name' => $data->userName,
                    'email' => $data->userEmail,
                    'password' => Hash::make($data->userPassword),
                ]);

                $exitCode = Artisan::call('app:tenant-migrations', [
                    'company' => $company->company,
                ]);
                $output = Artisan::output();

                if ($exitCode !== 0) {
                    throw new RuntimeException('Falha ao executar as migrations do tenant.');
                }

                return $company;
            });
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Não foi possível cadastrar a empresa. Nenhum registro foi criado.',
                'errors' => [
                    'migration' => [$exception->getMessage()],
                ],
                'output' => $output ?: null,
            ], 500);
        } finally {
            DatabaseHelper::changeSchema(schema: 'public');
        }

        return response()->json([
            'data' => CompanyData::fromModel($company),
            'migration_output' => $output,
        ], 201);
    }

    public function update(UpdateCompanyData $data, Company $company): JsonResponse
    {
        try {
            DB::transaction(function () use ($data, $company) {
                $company->update([
                    'name' => $data->name,
                    'email' => $data->email,
                    'document' => $data->document,
                    'contract' => $data->contract,
                    'until' => $data->until,
                    'active' => $data->active,
                ]);

                $user = $company->users()->where('id', '!=', 1)->first();

                if ($user && $data->userName !== null && $data->userEmail !== null) {
                    $userData = [
                        'name' => $data->userName,
                        'email' => $data->userEmail,
                    ];

                    if ($data->userPassword) {
                        $userData['password'] = Hash::make($data->userPassword);
                    }

                    $user->update($userData);
                } elseif (! $user && $data->userName && $data->userEmail && $data->userPassword) {
                    $company->users()->create([
                        'name' => $data->userName,
                        'email' => $data->userEmail,
                        'password' => Hash::make($data->userPassword),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Não foi possível atualizar a empresa.',
                'errors' => [
                    'company' => [$exception->getMessage()],
                ],
            ], 500);
        }

        return response()->json([
            'data' => CompanyData::fromModel($company->fresh()),
        ]);
    }
}
