<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAffectationRequest;
use App\Http\Requests\Admin\UpdateAffectationRequest;
use App\Http\Resources\Admin\AffectationResource;
use App\Models\Affectation;
use App\Services\Admin\AffectationService;

class AffectationController extends Controller
{
    public function __construct(private readonly AffectationService $affectationService)
    {
    }

    public function index()
    {
        return AffectationResource::collection($this->affectationService->getAll());
    }

    public function store(StoreAffectationRequest $request)
    {
        try {
            $affectation = $this->affectationService->create($request->validated());

            return (new AffectationResource($affectation->load(['interne', 'service', 'periodeRotation'])))
                ->response()
                ->setStatusCode(201);
        } catch (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status());
        }
    }

    public function show(Affectation $affectation)
    {
        return new AffectationResource($affectation->load(['interne', 'service', 'periodeRotation']));
    }

    public function update(UpdateAffectationRequest $request, Affectation $affectation)
    {
        try {
            return new AffectationResource($this->affectationService->update($affectation, $request->validated()));
        } catch (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status());
        }
    }

    public function destroy(Affectation $affectation)
    {
        $this->affectationService->delete($affectation);

        return response()->noContent();
    }
}
