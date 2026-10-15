<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffectationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'interne' => $this->whenLoaded('interne', function () {
                return [
                    'id' => $this->interne?->id,
                    'name' => $this->interne?->name,
                ];
            }),
            'service' => $this->whenLoaded('service', function () {
                return [
                    'id' => $this->service?->id,
                    'name' => $this->service?->name,
                    'hopital' => $this->service?->hopital?->name,
                ];
            }),
            'periode_rotation' => $this->whenLoaded('periodeRotation', function () {
                return [
                    'id' => $this->periodeRotation?->id,
                    'name' => $this->periodeRotation?->name,
                    'start_date' => $this->periodeRotation?->start_date,
                    'end_date' => $this->periodeRotation?->end_date,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
