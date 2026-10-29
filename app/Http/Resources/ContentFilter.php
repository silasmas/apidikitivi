<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * "Jeunesse" content filter.
 */
class ContentFilter extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'filter_key' => $this->filter_key,
            'filter_name' => $this->filter_name,
            'filter_name_fr' => $this->getTranslation('filter_name', 'fr'),
            'filter_name_en' => $this->getTranslation('filter_name', 'en'),
            'filter_name_ln' => $this->getTranslation('filter_name', 'ln'),
            'type_ids' => array_map('intval', $this->type_ids ?? []),
            'category_ids' => array_map('intval', $this->category_ids ?? []),
            'position' => (int) $this->position,
        ];
    }
}
