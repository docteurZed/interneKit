<?php

use App\Models\Affectation;
use App\Models\Interne;
use App\Models\PeriodeRotation;
use App\Models\Service;
use Carbon\CarbonImmutable;

it('returns all internes and resolves service only from the period active today', function () {
    $today = CarbonImmutable::today(config('app.timezone'));
    $previousPeriod = PeriodeRotation::query()->create([
        'name' => 'Rotation terminée',
        'start_date' => $today->subMonths(2)->toDateString(),
        'end_date' => $today->subMonth()->toDateString(),
    ]);
    $currentPeriod = PeriodeRotation::query()->create([
        'name' => 'Rotation active',
        'start_date' => $today->subDays(5)->toDateString(),
        'end_date' => $today->addDays(5)->toDateString(),
    ]);

    $oldService = Service::query()->create(['name' => 'Ancien service']);
    $currentService = Service::query()->create(['name' => 'Service actuel']);
    $assigned = Interne::query()->create(['name' => 'Affecté', 'phone' => '+228 90 00 00 01']);
    $unassigned = Interne::query()->create(['name' => 'Sans affectation', 'phone' => null]);

    Affectation::query()->create([
        'interne_id' => $assigned->id,
        'service_id' => $oldService->id,
        'periode_rotation_id' => $previousPeriod->id,
    ]);
    Affectation::query()->create([
        'interne_id' => $assigned->id,
        'service_id' => $currentService->id,
        'periode_rotation_id' => $currentPeriod->id,
    ]);

    $this->getJson('/api/public/internes')
        ->assertOk()
        ->assertJsonPath('meta.date', $today->toDateString())
        ->assertJsonPath('meta.current_period.name', 'Rotation active')
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Affecté')
        ->assertJsonPath('data.0.phone', '+228 90 00 00 01')
        ->assertJsonPath('data.0.service', 'Service actuel')
        ->assertJsonPath('data.1.name', 'Sans affectation')
        ->assertJsonPath('data.1.service', null);
});
