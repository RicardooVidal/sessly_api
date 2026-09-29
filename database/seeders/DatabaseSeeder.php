<?php

namespace Database\Seeders;

use App\Domains\Patient\Entities\Patient;
use App\Helpers\DatabaseHelper;
use App\Models\Company;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $companies = Company::query()->count();
        if ($companies === 0) {
            Company::create([
                'company' => 'ricardorodriguesvidal',
                'name' => 'RICARDO RODRIGUES VIDAL',
                'email' => 'ricardoorv95@gmail.com',
                'document' => '36320890000166',
                'until' => '2199-12-31'
            ]);

            User::create([
                'name' => 'Root',
                'email' => 'ricardoorv95@gmail.com',
                'password' => Hash::make('Mudar@123'),
                'company_id' => 1
            ]);
        }
        DatabaseHelper::changeSchema(schema: 'ricardorodriguesvidal');
        Patient::factory()->count(200)->create();
    }
}
