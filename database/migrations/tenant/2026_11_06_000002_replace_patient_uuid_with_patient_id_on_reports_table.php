<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->bigInteger('patient_id')->nullable()->after('user_id');
        });

        DB::table('reports')
            ->select(['id', 'patient_uuid'])
            ->whereNotNull('patient_uuid')
            ->orderBy('id')
            ->chunkById(100, function ($reports): void {
                foreach ($reports as $report) {
                    $patientId = DB::table('patients')
                        ->where('uuid', $report->patient_uuid)
                        ->value('id');

                    if ($patientId !== null) {
                        DB::table('reports')
                            ->where('id', $report->id)
                            ->update(['patient_id' => $patientId]);
                    }
                }
            });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('patient_uuid');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->foreign('patient_id')->references('id')->on('patients');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->uuid('patient_uuid')->nullable()->after('user_id');
        });

        DB::table('reports')
            ->select(['id', 'patient_id'])
            ->whereNotNull('patient_id')
            ->orderBy('id')
            ->chunkById(100, function ($reports): void {
                foreach ($reports as $report) {
                    $patientUuid = DB::table('patients')
                        ->where('id', $report->patient_id)
                        ->value('uuid');

                    if ($patientUuid !== null) {
                        DB::table('reports')
                            ->where('id', $report->id)
                            ->update(['patient_uuid' => $patientUuid]);
                    }
                }
            });

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('patient_id');
        });
    }
};
