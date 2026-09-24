<?php

namespace App\Http\Requests;

use App\Data\Appointment\AppointmentData;
use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequest extends FormRequest
{
    public function rules(): array
    {
        return AppointmentData::rules($this->getValidationContext());
    }

    private function getValidationContext()
    {
        return new \Spatie\LaravelData\Support\Validation\ValidationContext(
            payload: $this->all(),
            fullPayload: $this->all(),
            dataClass: AppointmentData::class
        );
    }
}
