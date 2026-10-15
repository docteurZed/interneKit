<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Http\Resources\Admin\InterneResource;
use App\Http\Resources\Admin\ServiceResource;
use App\Models\Service;
use App\Services\Admin\AffectationService;
use App\Services\Admin\ServiceService;
use Carbon\CarbonImmutable;

class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceService $serviceService,
        private readonly AffectationService $affectationService
    ) {}

    public function index()
    {
        return ServiceResource::collection($this->serviceService->getAll());
    }

    public function store(StoreServiceRequest $request)
    {
        $service = $this->serviceService->create($request->validated());

        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    public function show(Service $service)
    {
        return new ServiceResource($service->load('hopital'));
    }

    public function update(UpdateServiceRequest $request, Service $service)
    {
        return new ServiceResource($this->serviceService->update($service, $request->validated()));
    }

    public function destroy(Service $service)
    {
        $this->serviceService->delete($service);

        return response()->noContent();
    }

    public function internesAtDate(Service $service)
    {
        try {
            $date = CarbonImmutable::parse(request('date', now()->toDateString()));
            $internes = $this->affectationService->getInternesByServiceAtDate($service->id, $date);

            return InterneResource::collection($internes);
        } catch (BusinessException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status());
        }
    }
}
