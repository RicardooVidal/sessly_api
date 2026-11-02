<?php

namespace Database\Factories;

use App\Domains\Patient\Entities\MedicalRecord;
use App\Domains\Patient\Entities\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalRecordFactory extends Factory
{
    protected $model = MedicalRecord::class;

    public function definition(): array
    {
        return [
            'record' => $this->faker->randomNumber(),
            'document' => $this->faker->filePath(),
        ];
    }

    public function withPatient(?Patient $patient = null): self
    {
        return $this->state(function () use ($patient) {
            return [
                'patient_id' => $patient?->id ?? Patient::factory(),
            ];
        });
    }
}
