<?php

namespace App\Http\Controllers;

use Youtube;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class YouTubeController extends Controller
{
    /**
     * Get the playlist content
     *
     * @param  $file
     * @param  $title
     * @param  $thumbnail
     * @return string
     */
    public static function store($file, $title, $thumbnail = null, $description = null): string
    {
        $video = Youtube::upload($file, [
            'title' => $title,
            'description' => $description
        ])->withThumbnail($thumbnail);

        return $video->getVideoId();
    }

    /**
     * Remove the specified resource from YouTube.
     *
     * @param  $videoID
     */
    public static function destroy($videoID)
    {
        Youtube::delete($videoID);
    }
}
