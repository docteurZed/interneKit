<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\GardeResource;
use App\Models\Garde;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GardeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $today = CarbonImmutable::today(config('app.timezone'));
        $startDate = $today->subDay();
        $endDate = $today->addDays(2);

        $gardes = Garde::query()
            ->with(['typeGarde:id,name', 'service:id,name,hopital_id', 'service.hopital:id,name', 'internes:id,name,phone'])
            ->whereDate('date_garde', '>=', $startDate->toDateString())
            ->whereDate('date_garde', '<=', $endDate->toDateString())
            ->orderBy('start')
            ->get();

        return GardeResource::collection($gardes)->additional([
            'meta' => [
                'today' => $today->toDateString(),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ]);
    }
}
