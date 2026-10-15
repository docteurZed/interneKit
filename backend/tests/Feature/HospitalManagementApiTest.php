<?php

use App\Models\Interne;
use App\Models\PeriodeRotation;
use App\Models\Service;
use App\Models\TypeGarde;
use App\Models\User;

function adminUser(): User
{
    return User::query()->create([
        'name' => 'Admin User',
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'role' => 'admin',
    ]);
}

it('creates and updates an interne', function () {
    $admin = adminUser();

    $response = $this->actingAs($admin)->postJson('/api/internes', [
        'name' => 'Interne A',
        'phone' => null,
    ])->assertCreated();

    $interneId = $response->json('data.id');

    $this->actingAs($admin)->putJson('/api/internes/'.$interneId, [
        'name' => 'Interne A Updated',
        'phone' => '+228 90 00 00 00',
    ])->assertOk()
        ->assertJsonPath('data.name', 'Interne A Updated');
});

it('creates a period and rejects incoherent dates', function () {
    $admin = adminUser();

    $this->actingAs($admin)->postJson('/api/periodes-rotation', [
        'name' => 'Periode invalide',
        'start_date' => '2026-10-20',
        'end_date' => '2026-10-10',
    ])->assertUnprocessable();

    $this->actingAs($admin)->postJson('/api/periodes-rotation', [
        'name' => 'Periode 1',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-31',
    ])->assertCreated();
});

it('assigns an interne to a service for a period', function () {
    $admin = adminUser();
    $interne = Interne::query()->create(['name' => 'Interne B']);
    $hopital = $this->actingAs($admin)->postJson('/api/hopitaux', [
        'name' => 'CHU Campus',
    ])->assertCreated();

    $service = $this->actingAs($admin)->postJson('/api/services', [
        'name' => 'Cardiologie',
        'hopital_id' => $hopital->json('data.id'),
    ])->assertCreated()
        ->assertJsonPath('data.hopital.name', 'CHU Campus');

    $periode = $this->actingAs($admin)->postJson('/api/periodes-rotation', [
        'name' => 'P1',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-31',
    ])->assertCreated();

    $this->actingAs($admin)->postJson('/api/affectations', [
        'interne_id' => $interne->id,
        'service_id' => $service->json('data.id'),
        'periode_rotation_id' => $periode->json('data.id'),
    ])->assertCreated();
});

it('requires a hospital for services and scopes service names to hospitals', function () {
    $admin = adminUser();
    $firstHospital = $this->actingAs($admin)->postJson('/api/hopitaux', ['name' => 'CHU A'])->assertCreated();
    $secondHospital = $this->actingAs($admin)->postJson('/api/hopitaux', ['name' => 'CHU B'])->assertCreated();

    $this->actingAs($admin)->postJson('/api/services', ['name' => 'Cardiologie'])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('hopital_id');

    $this->actingAs($admin)->postJson('/api/services', [
        'name' => 'Cardiologie',
        'hopital_id' => $firstHospital->json('data.id'),
    ])->assertCreated();

    $this->actingAs($admin)->postJson('/api/services', [
        'name' => 'Cardiologie',
        'hopital_id' => $secondHospital->json('data.id'),
    ])->assertCreated();

    $this->actingAs($admin)->postJson('/api/services', [
        'name' => 'Cardiologie',
        'hopital_id' => $firstHospital->json('data.id'),
    ])->assertUnprocessable();

    $this->actingAs($admin)
        ->deleteJson('/api/hopitaux/'.$firstHospital->json('data.id'))
        ->assertConflict();

    $this->assertDatabaseHas('hopitaux', [
        'id' => $firstHospital->json('data.id'),
        'name' => 'CHU A',
    ]);
});

it('rejects overlapping rotation periods on create and update', function () {
    $admin = adminUser();
    $firstPeriod = $this->actingAs($admin)->postJson('/api/periodes-rotation', [
        'name' => 'Première période',
        'start_date' => '2027-01-01',
        'end_date' => '2027-01-10',
    ])->assertCreated();

    $this->actingAs($admin)->postJson('/api/periodes-rotation', [
        'name' => 'Chevauchement au dernier jour',
        'start_date' => '2027-01-10',
        'end_date' => '2027-01-20',
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'La periode de rotation chevauche une periode existante.');

    $secondPeriod = $this->actingAs($admin)->postJson('/api/periodes-rotation', [
        'name' => 'Période suivante',
        'start_date' => '2027-01-11',
        'end_date' => '2027-01-20',
    ])->assertCreated();

    $this->actingAs($admin)->putJson('/api/periodes-rotation/'.$secondPeriod->json('data.id'), [
        'name' => 'Mise à jour chevauchante',
        'start_date' => '2027-01-09',
        'end_date' => '2027-01-15',
    ])->assertUnprocessable()
        ->assertJsonPath('message', 'La periode de rotation chevauche une periode existante.');

    $this->assertDatabaseHas('periodes_rotation', [
        'id' => $firstPeriod->json('data.id'),
        'name' => 'Première période',
    ]);
    $storedSecondPeriod = PeriodeRotation::query()->findOrFail($secondPeriod->json('data.id'));
    expect($storedSecondPeriod->name)->toBe('Période suivante')
        ->and($storedSecondPeriod->start_date->toDateString())->toBe('2027-01-11');
});

it('rejects duplicate assignment for same interne and period', function () {
    $admin = adminUser();
    $interne = Interne::query()->create(['name' => 'Interne C']);
    $service = Service::query()->create(['name' => 'Neurologie']);

    $periodeId = $this->actingAs($admin)->postJson('/api/periodes-rotation', [
        'name' => 'P2',
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-30',
    ])->json('data.id');

    $this->actingAs($admin)->postJson('/api/affectations', [
        'interne_id' => $interne->id,
        'service_id' => $service->id,
        'periode_rotation_id' => $periodeId,
    ])->assertCreated();

    $this->actingAs($admin)->postJson('/api/affectations', [
        'interne_id' => $interne->id,
        'service_id' => $service->id,
        'periode_rotation_id' => $periodeId,
    ])->assertStatus(422);
});

it('returns internes assigned to a service at a given date', function () {
    $admin = adminUser();
    $interne = Interne::query()->create(['name' => 'Interne D']);
    $service = Service::query()->create(['name' => 'Pediatrie']);

    $periodeId = $this->actingAs($admin)->postJson('/api/periodes-rotation', [
        'name' => 'P3',
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-31',
    ])->json('data.id');

    $this->actingAs($admin)->postJson('/api/affectations', [
        'interne_id' => $interne->id,
        'service_id' => $service->id,
        'periode_rotation_id' => $periodeId,
    ])->assertCreated();

    $this->actingAs($admin)
        ->getJson('/api/services/'.$service->id.'/internes?date=2026-12-15')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('calculates guard schedule on weekday from its type defaults', function () {
    $admin = adminUser();
    $service = Service::query()->create(['name' => 'Chirurgie']);
    $type = TypeGarde::query()->create([
        'name' => 'Jour',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'decalage_end' => 0,
    ]);

    $interne = Interne::query()->create(['name' => 'Interne Jour']);

    $response = $this->actingAs($admin)->postJson('/api/gardes', [
        'type_garde_id' => $type->id,
        'service_id' => $service->id,
        'date_garde' => '2026-10-12',
        'interne_ids' => [$interne->id],
    ])->assertCreated();

    $this->assertDatabaseHas('gardes', [
        'id' => $response->json('data.id'),
        'start' => '2026-10-12 08:00:00',
        'end' => '2026-10-12 16:00:00',
    ]);
});

it('uses the same type defaults for a weekend guard', function () {
    $admin = adminUser();
    $service = Service::query()->create(['name' => 'Urgences']);
    $type = TypeGarde::query()->create([
        'name' => 'Type W',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'decalage_end' => 0,
    ]);

    $interne = Interne::query()->create(['name' => 'Interne Weekend']);

    $response = $this->actingAs($admin)->postJson('/api/gardes', [
        'type_garde_id' => $type->id,
        'service_id' => $service->id,
        'date_garde' => '2026-10-11',
        'interne_ids' => [$interne->id],
    ])->assertCreated();

    $this->assertDatabaseHas('gardes', [
        'id' => $response->json('data.id'),
        'start' => '2026-10-11 08:00:00',
        'end' => '2026-10-11 16:00:00',
    ]);
});

it('calculates overnight guard ending next day', function () {
    $admin = adminUser();
    $service = Service::query()->create(['name' => 'Imagerie']);
    $type = TypeGarde::query()->create([
        'name' => 'Nuit',
        'start_time' => '20:00',
        'end_time' => '08:00',
        'decalage_end' => 1,
    ]);

    $interne = Interne::query()->create(['name' => 'Interne Nuit']);

    $response = $this->actingAs($admin)->postJson('/api/gardes', [
        'type_garde_id' => $type->id,
        'service_id' => $service->id,
        'date_garde' => '2026-10-12',
        'interne_ids' => [$interne->id],
    ])->assertCreated();

    $this->assertDatabaseHas('gardes', [
        'id' => $response->json('data.id'),
        'start' => '2026-10-12 20:00:00',
        'end' => '2026-10-13 08:00:00',
    ]);
});

it('associates multiple internes to one guard', function () {
    $admin = adminUser();
    $service = Service::query()->create(['name' => 'Nephrologie']);
    $type = TypeGarde::query()->create([
        'name' => 'Multiples',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'decalage_end' => 0,
    ]);

    $interne1 = Interne::query()->create(['name' => 'I1']);
    $interne2 = Interne::query()->create(['name' => 'I2']);

    $response = $this->actingAs($admin)->postJson('/api/gardes', [
        'type_garde_id' => $type->id,
        'service_id' => $service->id,
        'date_garde' => '2026-10-14',
        'interne_ids' => [$interne1->id, $interne2->id],
    ])->assertCreated();

    $this->assertDatabaseCount('garde_interne', 2);
});

it('rejects duplicated participant in payload', function () {
    $admin = adminUser();
    $service = Service::query()->create(['name' => 'Dermatologie']);
    $type = TypeGarde::query()->create([
        'name' => 'Dup check',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'decalage_end' => 0,
    ]);

    $interne = Interne::query()->create(['name' => 'I3']);

    $this->actingAs($admin)->postJson('/api/gardes', [
        'type_garde_id' => $type->id,
        'service_id' => $service->id,
        'date_garde' => '2026-10-15',
        'interne_ids' => [$interne->id, $interne->id],
    ])->assertUnprocessable();
});

it('detects overlapping guards for same interne', function () {
    $admin = adminUser();
    $service = Service::query()->create(['name' => 'Maternite']);
    $type = TypeGarde::query()->create([
        'name' => 'Conflit',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'decalage_end' => 0,
    ]);

    $interne = Interne::query()->create(['name' => 'I4']);

    $this->actingAs($admin)->postJson('/api/gardes', [
        'type_garde_id' => $type->id,
        'service_id' => $service->id,
        'date_garde' => '2026-10-16',
        'interne_ids' => [$interne->id],
    ])->assertCreated();

    $this->actingAs($admin)->postJson('/api/gardes', [
        'type_garde_id' => $type->id,
        'service_id' => $service->id,
        'date_garde' => '2026-10-16',
        'interne_ids' => [$interne->id],
    ])->assertStatus(422);
});

it('rejects a guard without participants', function () {
    $admin = adminUser();
    $type = TypeGarde::query()->create([
        'name' => 'Required participant',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'decalage_end' => 0,
    ]);

    $this->actingAs($admin)->postJson('/api/gardes', [
        'type_garde_id' => $type->id,
        'date_garde' => '2026-10-20',
    ])->assertUnprocessable()->assertJsonValidationErrorFor('interne_ids');

    $this->assertDatabaseCount('gardes', 0);
});

it('rejects a guard without a service', function () {
    $admin = adminUser();
    $type = TypeGarde::query()->create([
        'name' => 'Required service',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'decalage_end' => 0,
    ]);
    $interne = Interne::query()->create(['name' => 'Interne sans service']);

    $this->actingAs($admin)->postJson('/api/gardes', [
        'type_garde_id' => $type->id,
        'date_garde' => '2026-10-22',
        'interne_ids' => [$interne->id],
    ])->assertUnprocessable()->assertJsonValidationErrorFor('service_id');

    $this->assertDatabaseCount('gardes', 0);
});

it('does not allow removing the last guard participant', function () {
    $admin = adminUser();
    $service = Service::query()->create(['name' => 'Service témoin']);
    $type = TypeGarde::query()->create([
        'name' => 'Keep participant',
        'start_time' => '08:00',
        'end_time' => '16:00',
        'decalage_end' => 0,
    ]);
    $interne = Interne::query()->create(['name' => 'Last interne']);

    $garde = $this->actingAs($admin)->postJson('/api/gardes', [
        'type_garde_id' => $type->id,
        'service_id' => $service->id,
        'date_garde' => '2026-10-21',
        'interne_ids' => [$interne->id],
    ])->assertCreated();

    $this->actingAs($admin)
        ->deleteJson('/api/gardes/'.$garde->json('data.id').'/participants/'.$interne->id)
        ->assertUnprocessable();

    $this->assertDatabaseCount('garde_interne', 1);
});
