<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GardeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date_garde' => $this->date_garde?->toDateString(),
            'start' => $this->start,
            'end' => $this->end,
            'type' => $this->typeGarde?->name,
            'service' => $this->service?->name,
            'hopital' => $this->service?->hopital?->name,
            'internes' => $this->internes->map(fn ($interne) => [
                'id' => $interne->id,
                'name' => $interne->name,
                'phone' => $interne->phone,
            ])->values(),
        ];
    }
}
