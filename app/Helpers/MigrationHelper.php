<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class MigrationHelper
{
    public static function replaceEnumCheck(string $table, string $column, array $values): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $constraint = sprintf('%s_%s_check', $table, $column);

        DB::statement(sprintf(
            'ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s',
            self::quoteIdentifier($table),
            self::quoteIdentifier($constraint),
        ));

        $quotedValues = implode(', ', array_map(
            fn (string $value) => DB::getPdo()->quote($value),
            array_values(array_unique($values)),
        ));

        DB::statement(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s CHECK (%s IN (%s))',
            self::quoteIdentifier($table),
            self::quoteIdentifier($constraint),
            self::quoteIdentifier($column),
            $quotedValues,
        ));
    }

    private static function quoteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }
}
