<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\Garde;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GardeService
{
    public function __construct(private readonly HoraireGardeService $horaireGardeService) {}

    public function getAll(array $filters = [])
    {
        $query = Garde::query()->with(['typeGarde', 'service.hopital', 'internes']);

        if (isset($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }

        if (isset($filters['type_garde_id'])) {
            $query->where('type_garde_id', $filters['type_garde_id']);
        }

        if (isset($filters['interne_id'])) {
            $query->whereHas('internes', fn ($q) => $q->where('internes.id', $filters['interne_id']));
        }

        if (isset($filters['date_start'])) {
            $query->whereDate('date_garde', '>=', $filters['date_start']);
        }

        if (isset($filters['date_end'])) {
            $query->whereDate('date_garde', '<=', $filters['date_end']);
        }

        return $query->orderBy('start')->get();
    }

    public function create(array $data): Garde
    {
        return DB::transaction(function () use ($data) {
            $participants = collect($data['interne_ids'] ?? [])->unique()->values();
            $this->assertHasParticipants($participants);

            $date = CarbonImmutable::parse($data['date_garde']);
            $schedule = $this->horaireGardeService->resolveSchedule(
                $data['type_garde_id'],
                $date
            );

            $this->assertNoInterneConflicts($participants, $schedule['start'], $schedule['end']);

            $garde = Garde::query()->create([
                'type_garde_id' => $data['type_garde_id'],
                'service_id' => $data['service_id'],
                'date_garde' => $data['date_garde'],
                'start' => $schedule['start']->toDateTimeString(),
                'end' => $schedule['end']->toDateTimeString(),
            ]);

            if ($participants->isNotEmpty()) {
                $garde->internes()->sync($participants->all());
            }

            return $garde->load(['typeGarde', 'service.hopital', 'internes']);
        });
    }

    public function update(Garde $garde, array $data): Garde
    {
        return DB::transaction(function () use ($garde, $data) {
            $date = CarbonImmutable::parse($data['date_garde'] ?? $garde->date_garde->toDateString());
            $typeGardeId = (int) ($data['type_garde_id'] ?? $garde->type_garde_id);
            $serviceId = array_key_exists('service_id', $data) ? $data['service_id'] : $garde->service_id;

            $schedule = $this->horaireGardeService->resolveSchedule(
                $typeGardeId,
                $date
            );

            $participantIds = collect($data['interne_ids'] ?? $garde->internes()->pluck('internes.id')->all())
                ->unique()
                ->values();
            $this->assertHasParticipants($participantIds);

            $this->assertNoInterneConflicts($participantIds, $schedule['start'], $schedule['end'], $garde->id);

            $garde->update([
                'type_garde_id' => $typeGardeId,
                'service_id' => $serviceId,
                'date_garde' => $date->toDateString(),
                'start' => $schedule['start']->toDateTimeString(),
                'end' => $schedule['end']->toDateTimeString(),
            ]);

            if (array_key_exists('interne_ids', $data)) {
                $garde->internes()->sync($participantIds->all());
            }

            return $garde->load(['typeGarde', 'service.hopital', 'internes']);
        });
    }

    public function delete(Garde $garde): void
    {
        DB::transaction(function () use ($garde) {
            $garde->internes()->detach();
            $garde->delete();
        });
    }

    public function addParticipants(Garde $garde, array $interneIds): Garde
    {
        return DB::transaction(function () use ($garde, $interneIds) {
            $ids = collect($interneIds)->unique()->values();
            $merged = $garde->internes()->pluck('internes.id')->concat($ids)->unique()->values();

            $this->assertNoInterneConflicts($merged, CarbonImmutable::parse($garde->start), CarbonImmutable::parse($garde->end), $garde->id);

            $garde->internes()->syncWithoutDetaching($ids->all());

            return $garde->load(['typeGarde', 'service.hopital', 'internes']);
        });
    }

    public function removeParticipant(Garde $garde, int $interneId): Garde
    {
        $isParticipant = $garde->internes()->where('internes.id', $interneId)->exists();

        if ($isParticipant && $garde->internes()->count() <= 1) {
            throw new BusinessException('Une garde doit conserver au moins un interne participant.');
        }

        $garde->internes()->detach($interneId);

        return $garde->load(['typeGarde', 'service.hopital', 'internes']);
    }

    private function assertNoInterneConflicts(
        Collection $interneIds,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $ignoreGardeId = null
    ): void {
        if ($interneIds->isEmpty()) {
            return;
        }

        foreach ($interneIds as $interneId) {
            $query = Garde::query()
                ->whereHas('internes', fn ($q) => $q->where('internes.id', $interneId))
                ->where('start', '<', $end->toDateTimeString())
                ->where('end', '>', $start->toDateTimeString());

            if ($ignoreGardeId !== null) {
                $query->whereKeyNot($ignoreGardeId);
            }

            if ($query->exists()) {
                throw new BusinessException('Conflit horaire detecte pour un interne participant.');
            }
        }
    }

    private function assertHasParticipants(Collection $interneIds): void
    {
        if ($interneIds->isEmpty()) {
            throw new BusinessException('Une garde doit avoir au moins un interne participant.');
        }
    }
}
