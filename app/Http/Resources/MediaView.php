<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class MediaView extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $media = Media::find($this->id);

        if (!is_null($media)) {
            return [
                'id' => $media->id,
                'media_title' => $media->media_title,
                'media_description' => $media->media_description,
                'source' => $media->source,
                'belonging_count' => $media->belonging_count,
                'time_length' => $media->time_length,
                'media_url' => $media->media_url,
                'teaser_url' => $media->teaser_url,
                'author_names' => $media->author_names,
                'artist_names' => $media->artist_names,
                'writer' => $media->writer,
                'director' => $media->director,
                'published_date' => $media->published_date,
                'cover_url' => $media->cover_url != null ? (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://admindikitivi.jptshienda.com/public' . $media->cover_url : null,
                'thumbnail_url' => $media->thumbnail_url != null ? (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://admindikitivi.jptshienda.com/public' . $media->thumbnail_url : null,
                'price' => $media->price,
                'for_youth' => $media->for_youth,
                'is_live' => $media->is_live,
                'belongs_to' => $media->belongs_to,
                'type' => $media->type,
                'categories' => $media->categories,
                'created_at_ago' => $media->created_at_ago,
                'created_at' => $media->created_at,
                'updated_at_ago' => $media->updated_at_ago,
                'updated_at' => $media->updated_at,
                'user_id' => $media->user_id,
            ];

        } else {
            return [
                'id' => $this->id,
                'media' => Media::make($this->media),
                'user' => User::make($this->user),
                'created_at' => $this->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
                'created_at_ago' => timeAgo($this->created_at->format('Y-m-d H:i:s')),
                'updated_at_ago' => timeAgo($this->updated_at->format('Y-m-d H:i:s'))
            ];
        }
    }
}
