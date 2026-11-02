<?php

namespace App\Helpers;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseHelper
{
    public static function changeSchema(string $companyUuid = null, string $schema = 'public'): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($schema === 'public' && !$companyUuid) {
            DB::statement('SET search_path TO "public"');
            return;
        }

        DB::statement('SET search_path TO "public"');

        $company = Company::query()
            ->select(['uuid', 'company'])
            ->where('uuid', $companyUuid)
            ->first();

        $schema = Str::replace('"', '""', $company->company ?? $schema);
        DB::statement('SET search_path TO "' . $schema . '"');
    }
}
