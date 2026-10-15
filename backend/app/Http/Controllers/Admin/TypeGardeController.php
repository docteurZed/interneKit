<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTypeGardeRequest;
use App\Http\Requests\Admin\UpdateTypeGardeRequest;
use App\Http\Resources\Admin\TypeGardeResource;
use App\Models\TypeGarde;
use App\Services\Admin\TypeGardeService;

class TypeGardeController extends Controller
{
    public function __construct(private readonly TypeGardeService $typeGardeService)
    {
    }

    public function index()
    {
        return TypeGardeResource::collection($this->typeGardeService->getAll());
    }

    public function store(StoreTypeGardeRequest $request)
    {
        $typeGarde = $this->typeGardeService->create($request->validated());

        return (new TypeGardeResource($typeGarde))->response()->setStatusCode(201);
    }

    public function show(TypeGarde $typeGarde)
    {
        return new TypeGardeResource($typeGarde);
    }

    public function update(UpdateTypeGardeRequest $request, TypeGarde $typeGarde)
    {
        return new TypeGardeResource($this->typeGardeService->update($typeGarde, $request->validated()));
    }

    public function destroy(TypeGarde $typeGarde)
    {
        $this->typeGardeService->delete($typeGarde);

        return response()->noContent();
    }
}
