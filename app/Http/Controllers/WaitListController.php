<?php

namespace App\Http\Controllers;

use App\Data\WaitList\WaitListData;
use App\Domains\Patient\Services\WaitListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

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
        return $this->waitListService->create($request)->wrap('data');
    }

    public function destroy(string $uuid): Response
    {
        $this->waitListService->delete($uuid);

        return response()->noContent();
    }
}
