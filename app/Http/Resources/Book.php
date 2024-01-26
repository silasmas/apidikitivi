<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class Book extends JsonResource
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
            'book_title' => $this->book_title,
            'book_url' => $this->book_url,
            'author_names' => $this->author_names,
            'price' => $this->price,
            'for_youth' => $this->for_youth,
            'type' => Type::make($this->type),
            'created_at_ago' => timeAgo($this->created_at->format('Y-m-d H:i:s')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at_ago' => timeAgo($this->updated_at->format('Y-m-d H:i:s')),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
}
