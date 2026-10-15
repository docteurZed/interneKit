<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\InterneResource;
use App\Models\Interne;
use App\Models\PeriodeRotation;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InterneController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $today = CarbonImmutable::today(config('app.timezone'));
        $currentPeriod = PeriodeRotation::query()
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->orderBy('start_date')
            ->orderBy('id')
            ->first();

        $internes = Interne::query()
            ->with(['affectations' => fn ($query) => $query
                ->where('periode_rotation_id', $currentPeriod?->id ?? 0)
                ->with('service.hopital')])
            ->orderBy('name')
            ->get();

        return InterneResource::collection($internes)->additional([
            'meta' => [
                'date' => $today->toDateString(),
                'current_period' => $currentPeriod ? [
                    'id' => $currentPeriod->id,
                    'name' => $currentPeriod->name,
                    'start_date' => $currentPeriod->start_date,
                    'end_date' => $currentPeriod->end_date,
                ] : null,
            ],
        ]);
    }
}
