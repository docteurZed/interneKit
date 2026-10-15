<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\Affectation;
use App\Models\Interne;
use App\Models\PeriodeRotation;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AffectationService
{
    public function getAll()
    {
        return Affectation::query()
            ->with(['interne', 'service.hopital', 'periodeRotation'])
            ->latest()
            ->get();
    }

    public function create(array $data): Affectation
    {
        return DB::transaction(function () use ($data) {
            $this->assertAffectationUnique($data['interne_id'], $data['periode_rotation_id']);

            return Affectation::query()->create($data);
        });
    }

    public function update(Affectation $affectation, array $data): Affectation
    {
        return DB::transaction(function () use ($affectation, $data) {
            $this->assertAffectationUnique($data['interne_id'], $data['periode_rotation_id'], $affectation->id);

            $affectation->update($data);

            return $affectation->load(['interne', 'service.hopital', 'periodeRotation']);
        });
    }

    public function delete(Affectation $affectation): void
    {
        $affectation->delete();
    }

    public function getInternesByServiceAtDate(int $serviceId, CarbonInterface $atDate): Collection
    {
        return Interne::query()
            ->whereHas('affectations', function ($query) use ($serviceId, $atDate) {
                $query->where('service_id', $serviceId)
                    ->whereHas('periodeRotation', function ($periodQuery) use ($atDate) {
                        $periodQuery
                            ->whereDate('start_date', '<=', $atDate->toDateString())
                            ->whereDate('end_date', '>=', $atDate->toDateString());
                    });
            })
            ->with(['affectations' => function ($query) use ($serviceId, $atDate) {
                $query->where('service_id', $serviceId)
                    ->whereHas('periodeRotation', function ($periodQuery) use ($atDate) {
                        $periodQuery
                            ->whereDate('start_date', '<=', $atDate->toDateString())
                            ->whereDate('end_date', '>=', $atDate->toDateString());
                    })
                    ->with(['service.hopital', 'periodeRotation']);
            }])
            ->orderBy('name')
            ->get();
    }

    public function getAffectationsByPeriode(PeriodeRotation $periodeRotation)
    {
        return Affectation::query()
            ->with(['interne', 'service.hopital'])
            ->where('periode_rotation_id', $periodeRotation->id)
            ->orderBy('id')
            ->get();
    }

    private function assertAffectationUnique(int $interneId, int $periodeRotationId, ?int $ignoreId = null): void
    {
        $query = Affectation::query()
            ->where('interne_id', $interneId)
            ->where('periode_rotation_id', $periodeRotationId);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw new BusinessException('Une affectation existe deja pour cet interne et cette periode.');
        }
    }
}
