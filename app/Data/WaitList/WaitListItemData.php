<?php

namespace App\Data\WaitList;

use App\Data\SelectData;
use App\Domains\Patient\Entities\WaitList;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class WaitListItemData extends Data
{
    public function __construct(
        public string $uuid,
        public string $patientUuid,
        public ?string $createdAt,
        public SelectData $patient,
    ) {}

    public static function fromModel(WaitList $waitList): self
    {
        return new self(
            uuid: $waitList->uuid,
            patientUuid: $waitList->patient->uuid,
            createdAt: $waitList->created_at?->toIso8601String(),
            patient: SelectData::from($waitList->patient->only(['uuid', 'name'])),
        );
    }
}
