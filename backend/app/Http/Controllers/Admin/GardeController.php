<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddGardeParticipantsRequest;
use App\Http\Requests\Admin\StoreGardeRequest;
use App\Http\Requests\Admin\UpdateGardeRequest;
use App\Http\Resources\Admin\GardeResource;
use App\Models\Garde;
use App\Models\Interne;
use App\Models\Service;
use App\Services\Admin\GardeService;

class GardeController extends Controller
{
    public function __construct(private readonly GardeService $gardeService)
    {
    }

    public function index()
    {
        return GardeResource::collection($this->gardeService->getAll(request()->only([
            'service_id',
            'type_garde_id',
            'interne_id',
            'date_start',
            'date_end',
        ])));
    }

    public function store(StoreGardeRequest $request)
    {
        try {
            $garde = $this->gardeService->create($request->validated());

            return (new GardeResource($garde))->response()->setStatusCode(201);
        } catch (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status());
        }
    }

    public function show(Garde $garde)
    {
        return new GardeResource($garde->load(['typeGarde', 'service.hopital', 'internes']));
    }

    public function update(UpdateGardeRequest $request, Garde $garde)
    {
        try {
            return new GardeResource($this->gardeService->update($garde, $request->validated()));
        } catch (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status());
        }
    }

    public function destroy(Garde $garde)
    {
        $this->gardeService->delete($garde);

        return response()->noContent();
    }

    public function addParticipants(AddGardeParticipantsRequest $request, Garde $garde)
    {
        try {
            return new GardeResource($this->gardeService->addParticipants($garde, $request->validated('interne_ids')));
        } catch (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status());
        }
    }

    public function removeParticipant(Garde $garde, Interne $interne)
    {
        try {
            return new GardeResource($this->gardeService->removeParticipant($garde, $interne->id));
        } catch (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status());
        }
    }

    public function planningForInterne(Interne $interne)
    {
        return GardeResource::collection($this->gardeService->getAll([
            'interne_id' => $interne->id,
            'date_start' => request('date_start'),
            'date_end' => request('date_end'),
        ]));
    }

    public function betweenDatesForService(Service $service)
    {
        return GardeResource::collection($this->gardeService->getAll([
            'service_id' => $service->id,
            'date_start' => request('date_start'),
            'date_end' => request('date_end'),
        ]));
    }
}
