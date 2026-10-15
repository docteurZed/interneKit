<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hopital_id' => $this->hopital_id,
            'hopital' => $this->whenLoaded('hopital', fn () => $this->hopital ? [
                'id' => $this->hopital->id,
                'name' => $this->hopital->name,
            ] : null),
            'internes' => InterneResource::collection($this->whenLoaded('internes')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
