<?php

namespace App\Http\Controllers;

use Google_Client;
use Google_Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class YouTubeController extends Controller
{
    /**
     * Get the playlist content
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $result
     * @param  $msg
     * @return \Illuminate\Http\JsonResponse
     */
    public static function upload(Request $request, $playlist, $title, ): JsonResponse
    {
        $part = 'snippet';
        $type = 'video';
        $api_key = config('services.youtube.api_key');
        $api_url = config('services.youtube.api_url');
        $client_id = config('services.youtube.client_id');
        $client_secret = config('services.youtube.client_secret');
        $client = new Google_Client();

        $res = null;

        return response()->json($res, 200);
    }
}
