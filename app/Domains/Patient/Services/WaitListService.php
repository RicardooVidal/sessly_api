<?php

namespace App\Domains\Patient\Services;

use App\Data\WaitList\WaitListData;
use App\Data\WaitList\WaitListItemData;
use App\Domains\Patient\Entities\WaitList;
use App\Domains\Patient\Repositories\WaitListRepository;

class WaitListService
{
    public function __construct(
        private readonly WaitListRepository $waitListRepository
    ) {}

    public function getAll(): array
    {
        return $this->waitListRepository->getAll()
            ->map(fn (WaitList $waitList) => WaitListItemData::fromModel($waitList))
            ->all();
    }

    public function create(WaitListData $waitListData): WaitListData
    {
        return WaitListData::from([
            'uuid' => $this->waitListRepository->create($waitListData)->uuid,
            'patient_uuid' => $waitListData->patientUuid,
        ]);
    }

    public function delete(string $uuid): void
    {
        $this->waitListRepository->delete($uuid);
    }
}
