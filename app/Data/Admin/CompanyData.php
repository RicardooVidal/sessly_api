<?php

namespace App\Data\Admin;

use App\Models\Company;
use Spatie\LaravelData\Data;

class CompanyData extends Data
{
    public function __construct(
        public string $uuid,
        public string $company,
        public string $name,
        public string $email,
        public string $document,
        public ?string $contract,
        public string $until,
        public bool $active,
        public ?CompanyUserData $user,
    ) {}

    public static function fromModel(Company $company): self
    {
        $company->loadMissing('users');

        $user = $company->users->first(fn ($item) => (int) $item->id !== 1);

        return new self(
            uuid: $company->uuid,
            company: $company->company,
            name: $company->name,
            email: $company->email,
            document: $company->document,
            contract: $company->contract,
            until: (string) $company->until,
            active: (bool) $company->active,
            user: $user ? CompanyUserData::fromModel($user) : null,
        );
    }
}
