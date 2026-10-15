<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePeriodeRotationRequest;
use App\Http\Requests\Admin\UpdatePeriodeRotationRequest;
use App\Http\Resources\Admin\AffectationResource;
use App\Http\Resources\Admin\PeriodeRotationResource;
use App\Models\PeriodeRotation;
use App\Services\Admin\AffectationService;
use App\Services\Admin\PeriodeRotationService;

class PeriodeRotationController extends Controller
{
    public function __construct(
        private readonly PeriodeRotationService $periodeRotationService,
        private readonly AffectationService $affectationService
    ) {
    }

    public function index()
    {
        return PeriodeRotationResource::collection($this->periodeRotationService->getAll());
    }

    public function store(StorePeriodeRotationRequest $request)
    {
        try {
            $periode = $this->periodeRotationService->create($request->validated());

            return (new PeriodeRotationResource($periode))->response()->setStatusCode(201);
        } catch (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status());
        }
    }

    public function show(PeriodeRotation $periodeRotation)
    {
        return new PeriodeRotationResource($periodeRotation);
    }

    public function update(UpdatePeriodeRotationRequest $request, PeriodeRotation $periodeRotation)
    {
        try {
            return new PeriodeRotationResource($this->periodeRotationService->update($periodeRotation, $request->validated()));
        } catch (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status());
        }
    }

    public function destroy(PeriodeRotation $periodeRotation)
    {
        $this->periodeRotationService->delete($periodeRotation);

        return response()->noContent();
    }

    public function affectations(PeriodeRotation $periodeRotation)
    {
        return AffectationResource::collection($this->affectationService->getAffectationsByPeriode($periodeRotation));
    }
}
