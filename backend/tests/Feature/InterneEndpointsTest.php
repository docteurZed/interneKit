<?php

use App\Models\Interne;
use App\Models\User;

it('returns 401 when a guest lists internes', function () {
    $this->getJson('/api/internes')->assertUnauthorized();
});

it('returns 403 when a non-admin lists internes', function () {
    $user = User::query()->create([
        'name' => 'Regular User',
        'email' => 'user@example.test',
        'password' => 'password',
        'role' => 'user',
    ]);

    $this->actingAs($user)
        ->getJson('/api/internes')
        ->assertForbidden();
});

it('allows admins to manage internes with a nullable phone', function () {
    $admin = User::query()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.test',
        'password' => 'password',
        'role' => 'admin',
    ]);

    $createResponse = $this->actingAs($admin)
        ->postJson('/api/internes', [
            'name' => 'Amina Mensah',
            'phone' => null,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Amina Mensah')
        ->assertJsonPath('data.phone', null);

    $interne = Interne::query()->findOrFail($createResponse->json('data.id'));
    $this->assertModelExists($interne);

    $this->getJson('/api/internes')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/internes/'.$interne->id)
        ->assertOk()
        ->assertJsonPath('data.id', $interne->id);

    $this->putJson('/api/internes/'.$interne->id, [
        'name' => 'Amina Mensah Updated',
        'phone' => '+228 90 00 00 00',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Amina Mensah Updated')
        ->assertJsonPath('data.phone', '+228 90 00 00 00');

    $this->assertDatabaseHas('internes', [
        'id' => $interne->id,
        'name' => 'Amina Mensah Updated',
        'phone' => '+228 90 00 00 00',
    ]);

    $this->deleteJson('/api/internes/'.$interne->id)->assertNoContent();
    $this->assertModelMissing($interne);
});

it('returns validation errors when an admin creates an interne without a name', function () {
    $admin = User::query()->create([
        'name' => 'Admin User',
        'email' => 'admin-validation@example.test',
        'password' => 'password',
        'role' => 'admin',
    ]);

    $this->actingAs($admin)
        ->postJson('/api/internes', ['phone' => null])
        ->assertUnprocessable()
        ->assertJsonValidationErrorFor('name');

    $this->assertDatabaseCount('internes', 0);
});
