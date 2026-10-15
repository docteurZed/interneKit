<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\TypeGarde;
use Carbon\CarbonImmutable;

class HoraireGardeService
{
    public function resolveSchedule(int $typeGardeId, CarbonImmutable $date): array
    {
        $typeGarde = TypeGarde::query()->findOrFail($typeGardeId);
        $start = CarbonImmutable::parse($date->toDateString().' '.$typeGarde->start_time);
        $end = CarbonImmutable::parse($date->toDateString().' '.$typeGarde->end_time)
            ->addDays((int) $typeGarde->decalage_end);

        if ($end->lessThanOrEqualTo($start)) {
            throw new BusinessException('Les horaires de garde sont incoherents: la fin doit etre apres le debut.');
        }

        return ['start' => $start, 'end' => $end];
    }
}
