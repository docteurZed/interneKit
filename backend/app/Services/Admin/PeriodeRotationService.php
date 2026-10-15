<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\PeriodeRotation;

class PeriodeRotationService
{
    public function getAll()
    {
        return PeriodeRotation::query()->orderBy('start_date')->get();
    }

    public function create(array $data): PeriodeRotation
    {
        $this->assertNoOverlap($data['start_date'], $data['end_date']);

        return PeriodeRotation::query()->create($data);
    }

    public function update(PeriodeRotation $periodeRotation, array $data): PeriodeRotation
    {
        $this->assertNoOverlap($data['start_date'], $data['end_date'], $periodeRotation->id);

        $periodeRotation->update($data);

        return $periodeRotation;
    }

    public function delete(PeriodeRotation $periodeRotation): void
    {
        $periodeRotation->delete();
    }

    private function assertNoOverlap(string $startDate, string $endDate, ?int $ignoreId = null): void
    {
        $query = PeriodeRotation::query()
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw new BusinessException('La periode de rotation chevauche une periode existante.');
        }
    }
}
