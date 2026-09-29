<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropForeign($table->getTable() . '_appointment_id_foreign');
            $table->dropColumn('appointment_id');
            $table->bigInteger('patient_id');
            $table->uuid()->after('id');
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('patients');
        });
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->bigInteger('appointment_id');
            $table->foreign('appointment_id')->references('id')->on('appointments');
            $table->dropColumn('uuid');
            $table->dropSoftDeletes();
            $table->dropForeign($table->getTable() . '_patient_id_foreign');
            $table->dropColumn('patient_id');
        });
    }
};
