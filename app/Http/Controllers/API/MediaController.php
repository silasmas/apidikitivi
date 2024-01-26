<?php

namespace App\Http\Controllers\API;

use App\Models\Media;
use App\Models\Session;
use Illuminate\Http\Request;
use App\Http\Resources\Media as ResourcesMedia;
use App\Models\User;

class MediaController extends BaseController
{
    /**
     * Display a listing of the resource.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $medias = Media::orderByDesc('created_at')->get();

        if ($request->user_id != null) {
            $session = Session::where(['user_id', $request->user_id])->first();

            if ($session->medias() == null) {
                $session->medias()->attach($medias->pluck('id'));
            }

            if ($session->medias() != null) {
                $session->medias()->sync($medias->pluck('id'));
            }
        }

        if ($request->ip_address != null) {
            $session = Session::where(['ip_address', $request->ip_address])->first();

            if ($session->medias() == null) {
                $session->medias()->attach($medias->pluck('id'));
            }

            if ($session->medias() != null) {
                $session->medias()->sync($medias->pluck('id'));
            }
        }

        return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Get inputs
        $inputs = [
            'media_title' => $request->media_title,
            'media_url' => $request->media_url,
            'author_names' => $request->author_names,
            'price' => $request->price,
            'for_youth' => $request->for_youth,
            'type_id' => $request->type_id
        ];
        // Select all medias to check unique constraint
        $medias = Media::all();

        // Validate required fields
        if (trim($inputs['media_title']) == null) {
            return $this->handleError($inputs['media_title'], __('validation.required'), 400);
        }

        if (trim($inputs['media_url']) == null) {
            return $this->handleError($inputs['media_url'], __('validation.required'), 400);
        }

        // Check if media title already exists
        foreach ($medias as $another_media):
            if ($another_media->media_title == $inputs['media_title']) {
                return $this->handleError($inputs['media_title'], __('validation.custom.title.exists'), 400);
            }
        endforeach;

        $media = Media::create($inputs);

        return $this->handleResponse(new ResourcesMedia($media), __('notifications.create_media_success'));
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

            if ($session->medias() == null) {
                $session->medias()->attach([$media->id]);
            }

            if ($session->medias() != null) {
                $session->medias()->syncWithoutDetaching([$media->id]);
            }
        }

        if ($request->ip_address != null) {
            $session = Session::where(['ip_address', $request->ip_address])->first();

            if ($session->medias() == null) {
                $session->medias()->attach([$media->id]);
            }

            if ($session->medias() != null) {
                $session->medias()->syncWithoutDetaching([$media->id]);
            }
        }

        return $this->handleResponse(new ResourcesMedia($media), __('notifications.find_media_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Media  $media
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Media $media)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'media_title' => $request->media_title,
            'media_url' => $request->media_url,
            'author_names' => $request->author_names,
            'price' => $request->price,
            'for_youth' => $request->for_youth,
            'type_id' => $request->type_id
        ];
        // Select all medias to check unique constraint
        $medias = Media::all();
        $current_media = Media::find($inputs['id']);

        if ($inputs['media_title'] != null) {
            foreach ($medias as $another_media):
                if ($current_media->media_title != $inputs['media_title']) {
                    if ($another_media->media_title == $inputs['media_title']) {
                        return $this->handleError($inputs['media_title'], __('validation.custom.title.exists'), 400);
                    }
                }
            endforeach;

            $media->update([
                'media_title' => $request->media_title,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['media_url'] != null) {
            $media->update([
                'media_url' => $request->media_url,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['author_names'] != null) {
            $media->update([
                'author_names' => $request->author_names,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['price'] != null) {
            $media->update([
                'price' => $request->price,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['for_youth'] != null) {
            $media->update([
                'for_youth' => $request->for_youth,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['type_id'] != null) {
            $media->update([
                'type_id' => $request->type_id,
                'updated_at' => now(),
            ]);
        }

        return $this->handleResponse(new ResourcesMedia($media), __('notifications.update_media_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Media  $media
     * @return \Illuminate\Http\Response
     */
    public function destroy(Media $media)
    {
        $media->delete();

        $medias = Media::all();

        return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.delete_media_success'));
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
            $medias = Media::whereHas('sessions', function ($query) use ($request) {
                                $query->where('sessions.user_id', $request->user_id);
                            })->where([['medias.for_youth', $for_youth], ['medias.type_id', $type_id]])->orderByDesc('medias.created_at')->get();

            return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));

        } else if ($request->ip_address != null) {
            $medias = Media::whereHas('sessions', function ($query) use ($request) {
                                $query->where('sessions.ip_address', $request->ip_address);
                            })->where([['medias.for_youth', $for_youth], ['medias.type_id', $type_id]])->orderByDesc('medias.created_at')->get();

            return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));

        } else {
            $medias = Media::where([['for_youth', $for_youth], ['type_id', $type_id]])->get();

            return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));
        }
    }

    /**
     * Vote the media.
     *
     * @param  int $user_id
     * @param  int $media_id
     * @param  int $status_id
     * @return \Illuminate\Http\Response
     */
    public function setApprobation($user_id, $media_id, $status_id)
    {
        $user = User::find($user_id);

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        foreach ($user->medias as $med) {
            if ($med->pivot->media_id == null) {
                $user->medias()->attach([$media_id => [
                    'status_id' => $status_id
                ]]);
            }

            if ($med->pivot->media_id != null) {
                $user->medias()->sync([$media_id => [
                    'status_id' => $status_id
                ]]);
            }
        }
    }
}
