<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Light public view of a family profile (parent or child) for the "Qui regarde ?" screen.
 * Never exposes password, parental code or token.
 */
class Profile extends JsonResource
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
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'surname' => $this->surname,
            'username' => $this->username,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date,
            'age' => !empty($this->birth_date) ? $this->age() : null,
            'is_child' => $this->belongs_to != null,
            'belongs_to' => $this->belongs_to,
            'autoplay_enabled' => (bool) ($this->autoplay_enabled ?? true),
            'preferred_themes' => $this->preferred_themes ?? [],
            'avatar_url' => $this->avatar_url != null ? (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $request->getHttpHost() . '/public/storage/' . $this->avatar_url : null,
        ];
    }
}
