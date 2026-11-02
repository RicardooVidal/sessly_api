<?php

namespace App\Http\Controllers;

use App\Data\Contract\ContractData;
use App\Data\Contract\IndexContractData;
use App\Domains\Contract\Services\ContractService;
use App\Domains\Patient\Entities\Patient;
use App\Helpers\StorageHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('memory_limit', '128M');

class ContractController extends Controller
{
    public function __construct(
        private ContractService $contractService,
    ) {}

    public function index(IndexContractData $request): JsonResponse
    {
        $data = $this->contractService->getAll($request);

        return response()->json(['data' => $data]);
    }

    public function store(ContractData $request): ContractData
    {
        $document = StorageHelper::upload(
            file: $request->file,
            name: "contract_patient_{$request->patient->uuid}.{$request->file->extension()}",
            path: 'contracts'
        );

        return $this->contractService->create($request, $document)->wrap('data');
    }

    public function show(string $uuid): ContractData
    {
        return $this->contractService->getByUuid($uuid)->wrap('data');
    }

    //    public function update(ContractData $contract): ContractData
    //    {
    //        /** @var Patient $patient */
    //        $patient = Patient::query()->select(['id'])->where('uuid', $contract->patient->uuid)->firstOrFail();
    //
    //        $this->contractService->update($contract->except('file'), $patient);
    //
    //        return $this->show($contract->uuid);
    //    }

    public function destroy(string $uuid): Response
    {
        $this->contractService->delete($uuid);

        return response()->noContent();
    }
}
