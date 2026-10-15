<?php

namespace App\Services\Admin;

use App\Models\TypeGarde;

class TypeGardeService
{
    public function getAll()
    {
        return TypeGarde::query()->orderBy('name')->get();
    }

    public function create(array $data): TypeGarde
    {
        return TypeGarde::query()->create($data);
    }

    public function update(TypeGarde $typeGarde, array $data): TypeGarde
    {
        $typeGarde->update($data);

        return $typeGarde;
    }

    public function delete(TypeGarde $typeGarde): void
    {
        $typeGarde->delete();
    }
}
