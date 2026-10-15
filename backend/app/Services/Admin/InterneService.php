<?php

namespace App\Services\Admin;

use App\Models\Affectation;
use App\Models\Interne;
use Carbon\CarbonInterface;

class InterneService
{
    public function getAll(?CarbonInterface $atDate = null)
    {
        $date = ($atDate ?? now())->toDateString();

        return Interne::query()
            ->with([
                'affectations' => function ($query) use ($date) {
                    $query->whereHas('periodeRotation', function ($subQuery) use ($date) {
                        $subQuery
                            ->whereDate('start_date', '<=', $date)
                            ->whereDate('end_date', '>=', $date);
                    })
                        ->with('service')
                        ->latest('id');
                },
            ])
            ->latest()
            ->get();
    }

    public function create(array $data)
    {
        return Interne::query()->create($data);
    }

    public function update(Interne $interne, array $data)
    {
        $interne->update($data);

        return $interne;
    }

    public function delete(Interne $interne)
    {
        $interne->delete();
    }

    public function getCurrentAffectation(Interne $interne, ?CarbonInterface $atDate = null): ?Affectation
    {
        $date = ($atDate ?? now())->toDateString();

        return Affectation::query()
            ->where('interne_id', $interne->id)
            ->whereHas('periodeRotation', function ($query) use ($date) {
                $query
                    ->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date);
            })
            ->with('service')
            ->latest('id')
            ->first();
    }
}
