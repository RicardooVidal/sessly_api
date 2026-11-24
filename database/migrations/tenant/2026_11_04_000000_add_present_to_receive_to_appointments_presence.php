<?php

use App\Enums\PresenceEnum;
use App\Helpers\MigrationHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        MigrationHelper::replaceEnumCheck(
            'appointments',
            'presence',
            array_column(PresenceEnum::cases(), 'value'),
        );
    }

    public function down(): void
    {
        DB::table('appointments')
            ->where('presence', PresenceEnum::PRESENT_TO_RECEIVE->value)
            ->update(['presence' => PresenceEnum::PRESENT->value]);

        MigrationHelper::replaceEnumCheck('appointments', 'presence', [
            PresenceEnum::PENDING->value,
            PresenceEnum::PRESENT->value,
            PresenceEnum::ABSENT->value,
        ]);
    }
};
