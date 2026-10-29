<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Playback history entry of a profile.
 */
class WatchProgress extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'media_id' => $this->media_id,
            'position' => (int) $this->position_seconds,
            'duration' => (int) $this->duration_seconds,
            'completed' => (bool) $this->completed,
            'play_count' => (int) $this->play_count,
            'last_watched_at' => $this->last_watched_at?->format('Y-m-d H:i:s'),
            // epoch ms : directement comparable à l'horodatage local de l'app
            'updated_at_ms' => $this->last_watched_at ? $this->last_watched_at->getTimestampMs() : null,
            'media' => $this->whenLoaded('media', fn () => $this->media ? Media::make($this->media) : null),
        ];
    }
}
