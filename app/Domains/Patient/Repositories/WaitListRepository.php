<?php

namespace App\Domains\Patient\Repositories;

use App\Data\WaitList\WaitListData;
use App\Domains\Patient\Entities\Patient;
use App\Domains\Patient\Entities\WaitList;
use Illuminate\Support\Collection;

class WaitListRepository
{
    public function __construct(
        private readonly WaitList $waitList
    ) {}

    public function getAll(): Collection
    {
        return $this->waitList->query()
            ->with(['patient:id,uuid,name'])
            ->whereHas('patient')
            ->orderBy('created_at')
            ->get();
    }

    public function create(WaitListData $waitListData): WaitList
    {
        return $this->waitList->create([
            'patient_id' => Patient::query()
                ->where('uuid', $waitListData->patientUuid)
                ->firstOrFail()
                ->id,
        ]);
    }

    public function delete(string $uuid): void
    {
        $this->waitList->where('uuid', $uuid)->firstOrFail()->delete();
    }
}
