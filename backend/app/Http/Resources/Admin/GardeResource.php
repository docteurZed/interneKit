<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GardeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date_garde' => $this->date_garde,
            'start' => $this->start,
            'end' => $this->end,
            'type_garde' => $this->whenLoaded('typeGarde', function () {
                return [
                    'id' => $this->typeGarde?->id,
                    'name' => $this->typeGarde?->name,
                ];
            }),
            'service' => $this->whenLoaded('service', function () {
                return $this->service ? [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                    'hopital' => $this->service->hopital?->name,
                ] : null;
            }),
            'participants' => InterneResource::collection($this->whenLoaded('internes')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
