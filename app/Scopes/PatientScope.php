<?php

namespace App\Scopes;

use App\Data\Patient\IndexPatientData;
use App\Helpers\DocumentHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PatientScope implements Scope
{
    public function __construct(
        private ?IndexPatientData $filters = null
    ) {}

    public function apply(Builder $builder, Model $model): void
    {
        $builder
            ->when(
                $this->filters->uuid, fn ($query) => $query->where('uuid', $this->filters->uuid)
            )
            ->when(
                $this->filters->document, fn ($query) => $query->where('document', DocumentHelper::removeMask($this->filters->document))
            )
            ->when(
                $this->filters->name, fn ($query) => $query->where('name', 'like', "%{$this->filters->name}%")
            )
            ->when(
                isset($this->filters->active), fn ($query) => $query->where('active', $this->filters->active)
            )
            ->when(
                $this->filters->excludeWaitList,
                fn ($query) => $query->whereDoesntHave('waitList')
            );
    }
}
