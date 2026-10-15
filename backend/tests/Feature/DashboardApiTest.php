<?php

use App\Models\Interne;
use App\Models\User;

function dashboardUser(string $role): User
{
    return User::query()->create([
        'name' => ucfirst($role),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'role' => $role,
    ]);
}

it('rejects guests and non-admin users on the dashboard', function () {
    $this->getJson('/api/dashboard')->assertUnauthorized();

    $user = dashboardUser('user');
    $this->actingAs($user)->getJson('/api/dashboard')->assertForbidden();
});

it('returns an empty but well-formed dashboard', function () {
    $this->actingAs(dashboardUser('admin'))
        ->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonPath('data.kpis.internes', 0)
        ->assertJsonPath('data.kpis.periode_en_cours', null)
        ->assertJsonCount(30, 'data.gardes_par_jour')
        ->assertJsonCount(6, 'data.gardes_par_mois')
        ->assertJsonPath('data.prochaines_gardes', []);
});

it('returns operational dashboard data', function () {
    Interne::query()->create(['name' => 'Interne']);

    $this->actingAs(dashboardUser('admin'))
        ->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonPath('data.kpis.internes', 1)
        ->assertJsonCount(30, 'data.gardes_par_jour')
        ->assertJsonCount(6, 'data.gardes_par_mois');
});
