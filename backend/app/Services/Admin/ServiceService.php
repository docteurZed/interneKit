<?php

namespace App\Services\Admin;

use App\Models\Service;

class ServiceService
{
    public function getAll()
    {
        return Service::query()->with('hopital')->orderBy('name')->get();
    }

    public function create(array $data): Service
    {
        return Service::query()->create($data)->load('hopital');
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($data);

        return $service->load('hopital');
    }

    public function delete(Service $service): void
    {
        $service->delete();
    }
}
