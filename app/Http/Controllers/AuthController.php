<?php

namespace App\Http\Controllers;

use App\Data\CompanyData;
use App\Data\CompanyDataCollection;
use App\Data\LoginData;
use App\Helpers\DatabaseHelper;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginData $request)
    {
        $credentials = $request->only('email', 'password')->toArray();

        if (! Auth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        /** @var Company $company */
        $company = $user->company()->first();

        $token = $user->createToken($user->name, ['*'], now()->addHour())->plainTextToken;

        if ((int) $user->id !== 1 && $company) {
            DatabaseHelper::changeSchema(schema: $company->uuid);
        }

        return response()->json(['data' => ['token' => $token]]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'company_id' => 'required|integer|exists:companies,id',
            'password' => 'required|string|min:8|confirmed',
        ]);

        /** @var Company $company */
        $company = Company::query()->find($validatedData['company_id']);

        $user = User::query()
            ->make([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);

        $user->company()->associate($company);
        $user->save();

        if ($user) {
            return response()->json([
                'data' => [
                    'token' => $user->createToken($user->name)->plainTextToken,
                ],
            ]);
        }
    }

    public function companies(Request $request): CompanyDataCollection
    {
        $user = $request->user();

        $companies = Company::query()
            ->select([
                'id',
                'uuid',
                'name',
            ])
            ->when((int) $user->id !== 1, fn ($query) => $query->whereKey($user->company_id))
            ->orderBy('name')
            ->get()
            ->map(fn (Company $company) => ['uuid' => $company->uuid, 'company' => $company->name]);

        return (new CompanyDataCollection(CompanyData::class, $companies))->wrap('data');
    }
}
