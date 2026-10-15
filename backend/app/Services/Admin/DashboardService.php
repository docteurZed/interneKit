<?php

namespace App\Services\Admin;

use App\Models\Affectation;
use App\Models\Garde;
use App\Models\Interne;
use App\Models\PeriodeRotation;
use App\Models\Service;
use App\Models\TypeGarde;
use Carbon\CarbonImmutable;

class DashboardService
{
    private const DAYS_BEFORE = 14;

    private const DAYS_AFTER = 15;

    private const MONTHS = 6;

    public function summary(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();

        $currentPeriod = PeriodeRotation::query()
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->orderBy('start_date')
            ->first();

        $monthStart = $today->startOfMonth();
        $monthEnd = $today->endOfMonth();
        $windowStart = $monthStart->subMonths(self::MONTHS - 1);

        $gardes = Garde::query()
            ->whereDate('date_garde', '>=', $windowStart->toDateString())
            ->whereDate('date_garde', '<=', $monthEnd->toDateString())
            ->get(['id', 'type_garde_id', 'date_garde']);

        $currentAffectations = fn ($query) => $query->whereHas('periodeRotation', fn ($period) => $period
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString()));

        $internesCount = Interne::query()->count();
        $assignedCount = Affectation::query()
            ->tap($currentAffectations)
            ->distinct()
            ->count('interne_id');

        return [
            'kpis' => [
                'internes' => $internesCount,
                'internes_affectes' => $assignedCount,
                'internes_sans_affectation' => max($internesCount - $assignedCount, 0),
                'services' => Service::query()->count(),
                'gardes_mois' => $gardes->filter(fn (Garde $garde) => $garde->date_garde->betweenIncluded($monthStart, $monthEnd))->count(),
                'gardes_a_venir' => Garde::query()
                    ->whereDate('date_garde', '>=', $today->toDateString())
                    ->whereDate('date_garde', '<=', $today->addDays(7)->toDateString())
                    ->count(),
                'periode_en_cours' => $currentPeriod ? [
                    'id' => $currentPeriod->id,
                    'name' => $currentPeriod->name,
                    'start_date' => $currentPeriod->start_date,
                    'end_date' => $currentPeriod->end_date,
                ] : null,
            ],
            'gardes_par_jour' => $this->gardesPerDay($gardes, $today),
            'gardes_par_mois' => $this->gardesPerMonth($gardes, $monthStart),
            'gardes_par_type' => $this->gardesPerType($gardes),
            'prochaines_gardes' => Garde::query()
                ->with(['typeGarde:id,name', 'service:id,name,hopital_id', 'service.hopital:id,name'])
                ->withCount('internes')
                ->where('start', '>=', now())
                ->orderBy('start')
                ->limit(5)
                ->get()
                ->map(fn (Garde $garde) => [
                    'id' => $garde->id,
                    'date_garde' => $garde->date_garde,
                    'start' => $garde->start,
                    'end' => $garde->end,
                    'type' => $garde->typeGarde?->name,
                    'service' => $garde->service?->name,
                    'hopital' => $garde->service?->hopital?->name,
                    'participants' => $garde->internes_count,
                ])
                ->all(),
        ];
    }

    private function gardesPerDay($gardes, CarbonImmutable $today): array
    {
        $countsByDate = $gardes->countBy(fn (Garde $garde) => $garde->date_garde->toDateString());
        $days = [];

        for ($offset = -self::DAYS_BEFORE; $offset <= self::DAYS_AFTER; $offset++) {
            $date = $today->addDays($offset)->toDateString();
            $days[] = ['date' => $date, 'count' => $countsByDate->get($date, 0)];
        }

        return $days;
    }

    private function gardesPerMonth($gardes, CarbonImmutable $currentMonth): array
    {
        $countsByMonth = $gardes->countBy(fn (Garde $garde) => $garde->date_garde->format('Y-m'));
        $months = [];

        for ($offset = self::MONTHS - 1; $offset >= 0; $offset--) {
            $month = $currentMonth->subMonths($offset)->format('Y-m');
            $months[] = ['month' => $month, 'count' => $countsByMonth->get($month, 0)];
        }

        return $months;
    }

    private function gardesPerType($gardes): array
    {
        $names = TypeGarde::query()->pluck('name', 'id');

        return $gardes
            ->countBy('type_garde_id')
            ->map(fn (int $count, int $typeId) => ['name' => $names->get($typeId, 'Type supprimé'), 'count' => $count])
            ->sortByDesc('count')
            ->values()
            ->all();
    }
}
