<?php

namespace App\Http\Controllers\API;

use App\Models\Media;
use App\Models\Session;
use Illuminate\Http\Request;
use App\Http\Resources\Media as ResourcesMedia;

class MediaController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $media = Media::find($id);

        if (is_null($media)) {
            return $this->handleError(__('notifications.find_media_404'));
        }

        if ($request->user_id != null) {
            $session = Session::where(['user_id', $request->user_id])->first();

            $session->medias()->syncWithoutDetaching([$media->id]);

        } else {
            if ($request->ip_address != null) {
                $session = Session::where(['ip_address', $request->ip_address])->first();

                $session->medias()->syncWithoutDetaching([$media->id]);
            }
        }

        return $this->handleResponse(new ResourcesMedia($media), __('notifications.find_media_success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Media $media)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Media $media)
    {
        //
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Get by age and type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $for_youth
     * @param  int $type_id
     * @return \Illuminate\Http\Response
     */
    public function findAllByAgeType(Request $request, $for_youth, $type_id)
    {
        if ($request->user_id != null) {
            $session = Session::where(['user_id', $request->user_id])->first();
            $medias = $session->medias();

            return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));

        } else if ($request->ip_address != null) {
            $session = Session::where(['ip_address', $request->ip_address])->first();
            $medias = $session->medias();

            return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));

        } else {
            $medias = Media::where([['for_youth', $for_youth], ['type_id', $type_id]])->get();

            return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));
        }
    }
}
