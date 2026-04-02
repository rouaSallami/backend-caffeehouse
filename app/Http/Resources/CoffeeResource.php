<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoffeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'category' => $this->category,
        'image' => $this->image,
        'available' => $this->available,
        'isNew' => $this->is_new,
        'description' => $this->description,
        'ingredients' => $this->ingredients->pluck('name')->values(),
        'sizes' => $this->sizes->map(function ($size) {
            return [
                'key' => $size->key,
                'label' => $size->label,
                'price' => (float) $size->price,
            ];
        })->values(),
    ];
}
}
