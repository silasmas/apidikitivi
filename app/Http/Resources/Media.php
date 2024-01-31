<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class Media extends JsonResource
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
            'media_title' => $this->media_title,
            'author' => $this->author,
            'writer' => $this->writer,
            'director' => $this->director,
            'cover_url' => $this->cover_url != null ? (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/public/storage/' . $this->cover_url : null,
            'price' => $this->price,
            'for_youth' => $this->for_youth,
            'belongs_to' => $this->belongs_to,
            'type' => Type::make($this->type),
            'user' => User::make($this->user),
            'user_approbations' => User::collection($this->user_approbations)->sortByDesc('created_at')->toArray(),
            'created_at_ago' => timeAgo($this->created_at->format('Y-m-d H:i:s')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at_ago' => timeAgo($this->updated_at->format('Y-m-d H:i:s')),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
}
