<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InterneRequest;
use App\Http\Resources\Admin\InterneResource;
use App\Models\Interne;
use App\Services\Admin\InterneService;
use Carbon\CarbonImmutable;

class InterneController extends Controller
{
    protected InterneService $service;

    public function __construct(InterneService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return InterneResource::collection($this->service->getAll());
    }

    public function store(InterneRequest $request)
    {
        return (new InterneResource($this->service->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Interne $interne)
    {
        $interne->setRelation('affectations', collect(array_filter([
            $this->service->getCurrentAffectation($interne),
        ])));

        return new InterneResource($interne);
    }

    public function update(InterneRequest $request, Interne $interne)
    {
        return new InterneResource($this->service->update($interne, $request->validated()));
    }

    public function destroy(Interne $interne)
    {
        $this->service->delete($interne);

        return response()->noContent();
    }

    public function currentService(Interne $interne)
    {
        $date = CarbonImmutable::parse(request('date', now()->toDateString()));
        $affectation = $this->service->getCurrentAffectation($interne, $date);

        return response()->json([
            'data' => [
                'interne_id' => $interne->id,
                'date' => $date->toDateString(),
                'service' => $affectation?->service ? [
                    'id' => $affectation->service->id,
                    'name' => $affectation->service->name,
                ] : null,
            ],
        ]);
    }
}
