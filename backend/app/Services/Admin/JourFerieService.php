<?php

namespace App\Services\Admin;

use App\Models\JourFerie;

class JourFerieService
{
    public function getAll()
    {
        return JourFerie::query()->orderBy('date')->get();
    }

    public function create(array $data): JourFerie
    {
        return JourFerie::query()->create($data);
    }

    public function update(JourFerie $jourFerie, array $data): JourFerie
    {
        $jourFerie->update($data);

        return $jourFerie;
    }

    public function delete(JourFerie $jourFerie): void
    {
        $jourFerie->delete();
    }
}
