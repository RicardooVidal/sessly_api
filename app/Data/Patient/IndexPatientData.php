<?php

namespace App\Data\Patient;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class IndexPatientData extends Data
{
    public function __construct(
        public ?string $uuid = null,
        public ?string $name = null,
        public ?string $document = null,
        public ?bool $active = null,
        public ?bool $excludeWaitList = null
    ) {}

    public static function rules(): array
    {
        return [
            'uuid' => [
                'nullable',
                'uuid',
            ],
            'name' => [
                'nullable',
                'string',
                'min:3',
                'max:255',
            ],
            'document' => [
                'nullable',
                'string',
                'max:11',
            ],
            'active' => [
                'nullable',
                'boolean',
            ],
            'exclude_wait_list' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
