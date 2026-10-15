<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentAffectation = $this->resource->relationLoaded('affectations')
            ? $this->affectations->first()
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'initials' => $this->initials,
            'phone' => $this->phone,
            'current_service' => $currentAffectation?->service ? [
                'id' => $currentAffectation->service->id,
                'name' => $currentAffectation->service->name,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
