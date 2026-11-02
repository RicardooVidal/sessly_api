<?php

namespace App\Domains\Appointment\DTO\Requests;

use Illuminate\Http\Request;
use Spatie\DataTransferObject\DataTransferObject;

class AppointmentParamsDTO extends DataTransferObject
{
    public int $patient_id;
    public string $date;
    public ?string $time;
    public static function fromRequest(Request $request): self
    {
        return new self(...$request->all());
    }

}
