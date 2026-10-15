<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function index(): JsonResponse
    {
        abort_unless(Auth::user()?->role === 'admin', 403);

        return response()->json(['data' => $this->dashboardService->summary()]);
    }
}
