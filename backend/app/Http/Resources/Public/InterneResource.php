<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentAssignment = $this->affectations->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'initials' => $this->initials,
            'phone' => $this->phone,
            'service' => $currentAssignment?->service?->name,
            'hopital' => $currentAssignment?->service?->hopital?->name,
        ];
    }
}
