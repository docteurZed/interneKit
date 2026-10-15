<?php

namespace App\Services\Admin;

use App\Models\Hopital;

class HopitalService
{
    public function getAll()
    {
        return Hopital::query()->withCount('services')->orderBy('name')->get();
    }

    public function create(array $data): Hopital
    {
        return Hopital::query()->create($data);
    }

    public function update(Hopital $hopital, array $data): Hopital
    {
        $hopital->update($data);

        return $hopital->loadCount('services');
    }

    public function delete(Hopital $hopital): void
    {
        $hopital->delete();
    }
}
