<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\YouTubeController;
use App\Models\Media;
use App\Models\Session;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\Media as ResourcesMedia;
use Illuminate\Support\Facades\Validator;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
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

        if ($request->hasHeader('X-user-id')) {
            $session = Session::where(['user_id', $request->header('X-user-id')])->first();

            if ($session->medias() == null) {
                $session->medias()->attach($medias->pluck('id'));
            }

            if ($session->medias() != null) {
                $session->medias()->sync($medias->pluck('id'));
            }
        }

        if ($request->hasHeader('X-ip-address')) {
            $session = Session::where(['ip_address', $request->header('X-ip-address')])->first();

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
            'teaser_url' => $request->teaser_url,
            'author_names' => $request->author_names,
            'writer' => $request->writer,
            'director' => $request->director,
            'cover_url' => $request->file('cover_url'),
            'price' => $request->price,
            'for_youth' => !empty($request->for_youth) ? $request->for_youth : 1,
            'belongs_to' => $request->belongs_to,
            'type_id' => $request->type_id,
            'user_id' => $request->user_id
        ];
        // Select all medias to check unique constraint
        $medias = Media::where('user_id', $inputs['user_id'])->get();

        // Validate required fields
        if (trim($inputs['media_title']) == null) {
            return $this->handleError($inputs['media_title'], __('validation.required'), 400);
        }

        if (trim($inputs['cover_url']) != null) {
            // Validate file mime type
            $validator = Validator::make($inputs, [
                'cover_url' => 'mimes:jpg,jpeg,png,gif'
            ]);

            if ($validator->fails()) {
                return $this->handleError($validator->errors());       
            }
        }

        // Check if media title already exists
        foreach ($medias as $another_media):
            if ($another_media->media_title == $inputs['media_title']) {
                return $this->handleError($inputs['media_title'], __('validation.custom.title.exists'), 400);
            }
        endforeach;

        $media = Media::create($inputs);
        $cover_url = 'images/medias/' . $media->id . '/' . Str::random(50) . '.' . $request->file('cover_url')->extension();

        // Upload image
        Storage::url(Storage::disk('public')->put($cover_url, $inputs['cover_url']));

        if ($request->file('youtube_video') != null) {
            $youtubeID = YouTubeController::store(
                $request->file('youtube_video')->getPathName(), 
                $inputs['media_title'], 
                $inputs['cover_url'], 
                $inputs['media_title'] . ' belonging to ' . $inputs['author_names']);

            $media->update([
                'media_url' => 'https://www.youtube.com/watch?v=' . $youtubeID,
                'updated_at' => now()
            ]);
        }

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

        if ($request->hasHeader('X-user-id')) {
            $session = Session::where(['user_id', $request->header('X-user-id')])->first();

            if ($session->medias() == null) {
                $session->medias()->attach([$media->id]);
            }

            if ($session->medias() != null) {
                $session->medias()->syncWithoutDetaching([$media->id]);
            }
        }

        if ($request->hasHeader('X-ip-address')) {
            $session = Session::where(['ip_address', $request->header('X-ip-address')])->first();

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
            'teaser_url' => $request->teaser_url,
            'author_names' => $request->author_names,
            'writer' => $request->writer,
            'director' => $request->director,
            'cover_url' => $request->cover_url,
            'price' => $request->price,
            'for_youth' => $request->for_youth,
            'belongs_to' => $request->belongs_to,
            'type_id' => $request->type_id,
            'user_id' => $request->user_id
        ];

        if ($inputs['media_title'] != null) {
            // Select all user medias to check unique constraint
            $medias = Media::where('user_id', $inputs['user_id'])->get();
            $current_media = Media::find($inputs['id']);

            foreach ($medias as $another_media):
                if ($current_media->media_title != $inputs['media_title']) {
                    if ($another_media->media_title == $inputs['media_title']) {
                        return $this->handleError($inputs['media_title'], __('validation.custom.title.exists'), 400);
                    }
                }
            endforeach;

            $media->update([
                'media_title' => $inputs['media_title'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['media_url'] != null) {
            $media->update([
                'media_url' => $inputs['media_url'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['teaser_url'] != null) {
            $media->update([
                'teaser_url' => $inputs['teaser_url'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['author_names'] != null) {
            $media->update([
                'author_names' => $inputs['author_names'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['writer'] != null) {
            $media->update([
                'writer' => $inputs['writer'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['director'] != null) {
            $media->update([
                'director' => $inputs['director'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['cover_url'] != null) {
            $media->update([
                'cover_url' => $inputs['cover_url'],
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

        if ($inputs['belongs_to'] != null) {
            $media->update([
                'belongs_to' => $request->belongs_to,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['type_id'] != null) {
            $media->update([
                'type_id' => $request->type_id,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['user_id'] != null) {
            $media->update([
                'user_id' => $request->user_id,
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
        if (str_starts_with('https://www.youtube.com', $media->media_url)) {
            preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $media->media_url, $match);

            $youtube_id = $match[1];

            YouTubeController::destroy($youtube_id);
        }

        $media->delete();

        $medias = Media::all();

        return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.delete_media_success'));
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Get all by title.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string $data
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request, $data)
    {
        $medias = Media::where('media_title', 'LIKE', '%' . $data . '%')->get();

        if ($request->hasHeader('X-user-id')) {
            $session = Session::where(['user_id', $request->header('X-user-id')])->first();

            if ($session->medias() == null) {
                $session->medias()->attach($medias->pluck('id'));
            }

            if ($session->medias() != null) {
                $session->medias()->sync($medias->pluck('id'));
            }
        }

        if ($request->hasHeader('X-ip-address')) {
            $session = Session::where(['ip_address', $request->header('X-ip-address')])->first();

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
     * Get by age and type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $for_youth
     * @param  int $type_id
     * @return \Illuminate\Http\Response
     */
    public function findAllByAgeType(Request $request, $for_youth, $type_id)
    {
        if ($request->hasHeader('X-user-id')) {
            $medias = Media::whereHas('sessions', function ($query) use ($request) {
                                $query->where('sessions.user_id', $request->header('X-user-id'));
                            })->where([['medias.for_youth', $for_youth], ['medias.type_id', $type_id]])->orderByDesc('medias.created_at')->get();

            return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));

        } else if ($request->hasHeader('X-ip-address')) {
            $medias = Media::whereHas('sessions', function ($query) use ($request) {
                                $query->where('sessions.ip_address', $request->header('X-ip-address'));
                            })->where([['medias.for_youth', $for_youth], ['medias.type_id', $type_id]])->orderByDesc('medias.created_at')->get();

            return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));

        } else {
            $medias = Media::where([['for_youth', $for_youth], ['type_id', $type_id]])->get();

            return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));
        }
    }

    /**
     * Approve the media.
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

    /**
     * Add media cover in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function addImage(Request $request, $id)
    {
        $inputs = [
            'media_id' => $request->entity_id,
            'image_64' => $request->base64image
        ];

        // $extension = explode('/', explode(':', substr($inputs['image_64'], 0, strpos($inputs['image_64'], ';')))[1])[1];
        $replace = substr($inputs['image_64'], 0, strpos($inputs['image_64'], ',') + 1);
        // Find substring from replace here eg: data:image/png;base64,
        $image = str_replace($replace, '', $inputs['image_64']);
        $image = str_replace(' ', '+', $image);

        // Clean selected "medias" directory
        $file = new Filesystem;
        $file->cleanDirectory($_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/medias/' . $inputs['media_id']);
        // Create image URL
        $image_url = 'images/medias/' . $inputs['media_id'] . '/' . Str::random(50) . '.png';

        // Upload image
        Storage::url(Storage::disk('public')->put($image_url, base64_decode($image)));

		$media = Media::find($id);

        $media->update([
            'cover_url' => $image_url,
            'updated_at' => now()
        ]);

        return $this->handleResponse(new ResourcesMedia($media), __('notifications.update_media_success'));
	}
}
