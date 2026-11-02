<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->boolean('create_appointments')->default(false)->after('active');
            $table->boolean('notify_appointment')->default(false)->after('create_appointments');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('create_appointments');
            $table->dropColumn('notify_appointment');
        });
    }
};
