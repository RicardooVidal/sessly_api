<?php

namespace App\Http\Controllers;

use App\Domains\Dashboard\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function summary(): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboardService->summary()->toArray(),
        ]);
    }
}
