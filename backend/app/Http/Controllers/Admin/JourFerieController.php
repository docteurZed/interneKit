<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJourFerieRequest;
use App\Http\Requests\Admin\UpdateJourFerieRequest;
use App\Http\Resources\Admin\JourFerieResource;
use App\Models\JourFerie;
use App\Services\Admin\JourFerieService;

class JourFerieController extends Controller
{
    public function __construct(private readonly JourFerieService $jourFerieService)
    {
    }

    public function index()
    {
        return JourFerieResource::collection($this->jourFerieService->getAll());
    }

    public function store(StoreJourFerieRequest $request)
    {
        $jourFerie = $this->jourFerieService->create($request->validated());

        return (new JourFerieResource($jourFerie))->response()->setStatusCode(201);
    }

    public function show(JourFerie $jourFerie)
    {
        return new JourFerieResource($jourFerie);
    }

    public function update(UpdateJourFerieRequest $request, JourFerie $jourFerie)
    {
        return new JourFerieResource($this->jourFerieService->update($jourFerie, $request->validated()));
    }

    public function destroy(JourFerie $jourFerie)
    {
        $this->jourFerieService->delete($jourFerie);

        return response()->noContent();
    }
}
