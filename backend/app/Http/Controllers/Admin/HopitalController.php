<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHopitalRequest;
use App\Http\Requests\Admin\UpdateHopitalRequest;
use App\Http\Resources\Admin\HopitalResource;
use App\Models\Hopital;
use App\Services\Admin\HopitalService;

class HopitalController extends Controller
{
    public function __construct(private readonly HopitalService $hopitalService) {}

    public function index()
    {
        return HopitalResource::collection($this->hopitalService->getAll());
    }

    public function store(StoreHopitalRequest $request)
    {
        return (new HopitalResource($this->hopitalService->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Hopital $hopital)
    {
        return new HopitalResource($hopital->loadCount('services'));
    }

    public function update(UpdateHopitalRequest $request, Hopital $hopital)
    {
        return new HopitalResource($this->hopitalService->update($hopital, $request->validated()));
    }

    public function destroy(Hopital $hopital)
    {
        if ($hopital->services()->exists()) {
            return response()->json([
                'message' => 'Suppression impossible: cet hôpital possède encore des services.',
            ], 409);
        }

        $this->hopitalService->delete($hopital);

        return response()->noContent();
    }
}
