<?php

namespace App\Http\Controllers;

use App\Data\WaitList\WaitListData;
use App\Domains\Patient\Services\WaitListService;
use Illuminate\Http\JsonResponse;

class WaitListController extends Controller
{
    public function __construct(
        private readonly WaitListService $waitListService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->waitListService->getAll()]);
    }

    public function store(WaitListData $request): WaitListData
    {
        return $this->waitListService->create($request);
    }

    public function destroy(string $uuid): void
    {
        $this->waitListService->delete($uuid);
    }
}
