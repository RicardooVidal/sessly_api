<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->uuid('patient_uuid')->nullable();
            $table->date('reference_month');
            $table->string('status', 20)->default('queued');
            $table->string('batch_id')->nullable();
            $table->unsignedInteger('total_patients')->default(0);
            $table->unsignedInteger('processed_patients')->default(0);
            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['reference_month', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
