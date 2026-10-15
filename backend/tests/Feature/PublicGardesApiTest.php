<?php

use App\Models\Garde;
use App\Models\Interne;
use App\Models\Service;
use App\Models\TypeGarde;
use Carbon\CarbonImmutable;

it('returns the four-day public schedule with assigned internes and their phone numbers', function () {
    $service = Service::query()->create(['name' => 'Urgences']);
    $type = TypeGarde::query()->create([
        'name' => 'Garde de jour',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'decalage_end' => 0,
    ]);
    $interne = Interne::query()->create(['name' => 'Interne de garde', 'phone' => '+228 90 00 00 00']);
    $today = CarbonImmutable::today(config('app.timezone'));

    foreach ([-2, -1, 0, 1, 2, 3] as $offset) {
        $date = $today->addDays($offset);
        $garde = Garde::query()->create([
            'type_garde_id' => $type->id,
            'service_id' => $service->id,
            'date_garde' => $date->toDateString(),
            'start' => $date->setTime(8, 0),
            'end' => $date->setTime(16, 0),
        ]);
        $garde->internes()->attach($interne->id);
    }

    $this->getJson('/api/public/gardes')
        ->assertOk()
        ->assertJsonPath('meta.today', $today->toDateString())
        ->assertJsonPath('meta.start_date', $today->subDay()->toDateString())
        ->assertJsonPath('meta.end_date', $today->addDays(2)->toDateString())
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('data.0.date_garde', $today->subDay()->toDateString())
        ->assertJsonPath('data.1.date_garde', $today->toDateString())
        ->assertJsonPath('data.2.date_garde', $today->addDay()->toDateString())
        ->assertJsonPath('data.3.date_garde', $today->addDays(2)->toDateString())
        ->assertJsonPath('data.1.internes.0.name', 'Interne de garde')
        ->assertJsonPath('data.1.internes.0.phone', '+228 90 00 00 00');
});
