<?php

namespace App\Http\Controllers;

use App\Data\Finance\IndexFinanceData;
use App\Domains\Finance\Services\FinanceService;
use Illuminate\Http\JsonResponse;

class FinanceController extends Controller
{
    public function __construct(
        private readonly FinanceService $financeService
    ) {}

    public function summary(IndexFinanceData $filters): JsonResponse
    {
        return response()->json([
            'data' => $this->financeService->summary($filters)->toArray(),
        ]);
    }
}
