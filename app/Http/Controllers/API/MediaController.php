<?php

namespace App\Http\Controllers\API;

use App\Models\Media;
use App\Models\Notification;
use App\Models\Session;
use App\Models\Status;
use App\Models\Type;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\YouTubeController;
use App\Http\Resources\Media as ResourcesMedia;
use App\Http\Resources\User as ResourcesUser;
use App\Http\Resources\Session as ResourcesSession;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class MediaController extends BaseController
{
    /**
     * Display a listing of the resource.
     * 
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $medias = Media::orderByDesc('created_at')->get();

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
            'media_description' => $request->media_description,
            'media_url' => $request->media_url,
            'teaser_url' => $request->file('teaser_url'),
            'author_names' => $request->author_names,
            'artist_names' => $request->artist_names,
            'writer' => $request->writer,
            'director' => $request->director,
            'published_date' => $request->published_date,
            'cover_url' => $request->file('cover_url'),
            'price' => $request->price,
            'for_youth' => $request->for_youth,
            'is_live' => $request->is_live,
            'belongs_to' => $request->belongs_to,
            'type_id' => $request->type_id,
            'user_id' => $request->user_id
        ];
        // Select all medias to check unique constraint
        $medias = Media::where('user_id', $inputs['user_id'])->get();

        // Validate required fields
        if ($inputs['type_id'] == null) {
            return $this->handleError($inputs['type_id'], __('validation.custom.type.required'), 400);
        }

        if (trim($inputs['media_title']) == null) {
            return $this->handleError($inputs['media_title'], __('validation.custom.title.required'), 400);
        }

        // Check if media title already exists
        foreach ($medias as $another_media):
            if ($another_media->media_title == $inputs['media_title']) {
                return $this->handleError($inputs['media_title'], __('validation.custom.title.exists'), 400);
            }
        endforeach;

        $media = Media::create($inputs);

		if ($request->file('teaser_url') != null) {
			$teaser_url = 'images/medias/' . $media->id . '/teaser.' . $request->file('teaser_url')->extension();

			// Upload teaser
			Storage::url(Storage::disk('public')->put($teaser_url, $inputs['teaser_url']));

            $media->update([
                'teaser_url' => $teaser_url,
                'updated_at' => now()
            ]);
        }

		if ($request->file('cover_url') != null) {
			// Upload cover
			$request->cover_url->storeAs('images/medias/' . $media->id, 'cover.' . $request->file('cover_url')->extension());

			$cover_url = 'images/medias/' . $media->id . '/cover.' . $request->file('cover_url')->extension();

            $media->update([
                'cover_url' => $cover_url,
                'updated_at' => now()
            ]);
        }

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

        if ($request->categories_ids) {
            $media->categories()->attach($request->categories_ids);
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

        if ($request->hasHeader('X-user-id') AND $request->hasHeader('X-ip-address') OR $request->hasHeader('X-user-id') AND !$request->hasHeader('X-ip-address')) {
            $session = Session::where('user_id', $request->header('X-user-id'))->first();

            if (!empty($session)) {
                if ($session->medias() == null) {
                    $session->medias()->attach([$media->id]);
                }

                if ($session->medias() != null) {
                    $session->medias()->sync([$media->id]);
                }

            }
        }

        if ($request->hasHeader('X-ip-address')) {
            $session = Session::where('ip_address', $request->header('X-ip-address'))->first();

            if (!empty($session)) {
                if ($session->medias() == null) {
                    $session->medias()->attach([$media->id]);
                }

                if ($session->medias() != null) {
                    $session->medias()->sync([$media->id]);
                }

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
            'media_description' => $request->media_description,
            'media_url' => $request->media_url,
            'teaser_url' => $request->file('teaser_url'),
            'author_names' => $request->author_names,
            'artist_names' => $request->artist_names,
            'writer' => $request->writer,
            'director' => $request->director,
            'published_date' => $request->published_date,
            'cover_url' => $request->file('cover_url'),
            'price' => $request->price,
            'for_youth' => $request->for_youth,
            'is_live' => $request->is_live,
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

        if ($inputs['media_description'] != null) {
            $media->update([
                'media_description' => $inputs['media_description'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['media_url'] != null) {
            $media->update([
                'media_url' => $inputs['media_url'],
                'updated_at' => now(),
            ]);
        }

		if ($request->file('teaser_url') != null) {
			$teaser_url = 'images/medias/' . $media->id . '/teaser.' . $request->file('teaser_url')->extension();

			// Upload teaser
			Storage::url(Storage::disk('public')->put($teaser_url, $inputs['teaser_url']));

            $media->update([
                'teaser_url' => $teaser_url,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['author_names'] != null) {
            $media->update([
                'author_names' => $inputs['author_names'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['artist_names'] != null) {
            $media->update([
                'artist_names' => $inputs['artist_names'],
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

        if ($inputs['published_date'] != null) {
            $media->update([
                'published_date' => $inputs['published_date'],
                'updated_at' => now(),
            ]);
        }

		if ($request->file('cover_url') != null) {
			// Upload cover
			$request->cover_url->storeAs('images/medias/' . $media->id, 'cover.' . $request->file('cover_url')->extension());

			$cover_url = 'images/medias/' . $media->id . '/cover.' . $request->file('cover_url')->extension();

            $media->update([
                'cover_url' => $cover_url,
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
                'for_youth' => $inputs['for_youth'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['is_live'] != null) {
            $media->update([
                'is_live' => $inputs['is_live'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['belongs_to'] != null) {
            $media->update([
                'belongs_to' => $inputs['belongs_to'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['type_id'] != null) {
            $media->update([
                'type_id' => $inputs['type_id'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['user_id'] != null) {
            $media->update([
                'user_id' => $inputs['user_id'],
                'updated_at' => now(),
            ]);
        }

        if ($request->categories_ids) {
            $media->categories()->syncWithoutDetaching($request->categories_ids);
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
     * Find current trends.
     *
     * @param  string $year
     * @param  int $for_youth
     * @return \Illuminate\Http\Response
     */
    public function trends($year, $for_youth)
    {
        $medias = Media::whereHas('sessions', function($query) use ($year) {
                            $query->whereMonth('sessions.created_at', '>=', date('m'))
                                    ->whereYear('sessions.created_at', '=', $year);
                        })->where('for_youth', $for_youth)->distinct()->limit(5)->get();

        return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));
    }

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

        if ($request->hasHeader('X-user-id') AND $request->hasHeader('X-ip-address') OR $request->hasHeader('X-user-id') AND !$request->hasHeader('X-ip-address')) {
            $session = Session::where('user_id', $request->header('X-user-id'))->first();

            if (!empty($session)) {
                if ($session->medias() == null) {
                    $session->medias()->attach($medias->pluck('id'));
                }

                if ($session->medias() != null) {
                    $session->medias()->sync($medias->pluck('id'));
                }
            }
        }

        if ($request->hasHeader('X-ip-address')) {
            $session = Session::where('ip_address', $request->header('X-ip-address'))->first();

            if (!empty($session)) {
                if ($session->medias() == null) {
                    $session->medias()->attach($medias->pluck('id'));
                }

                if ($session->medias() != null) {
                    $session->medias()->sync($medias->pluck('id'));
                }
            }
        }

        return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));
    }

    /**
     * Get by type.
     *
     * @param  int $for_youth
     * @return \Illuminate\Http\Response
     */
    public function findLive($for_youth)
    {
        $type = Type::where('type_name->fr', 'Programme TV')->first();

        if (is_null($type)) {
            return $this->handleError(__('notifications.find_type_404'));
        }

        $medias = Media::where([['for_youth', $for_youth], ['is_live', 1], ['type_id', $type->id]])->get();

        return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));
    }

    /**
     * Get by type.
     *
     * @param  string $locale
     * @param  string $type_name
     * @return \Illuminate\Http\Response
     */
    public function findAllByType($locale, $type_name)
    {
        $type = Type::where('type_name->' . $locale, $type_name)->first();

        if (is_null($type)) {
            return $this->handleError(__('notifications.find_type_404'));
        }

        $medias = Media::where('type_id', $type->id)->get();

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
        if ($request->hasHeader('X-user-id') AND $request->hasHeader('X-ip-address') OR $request->hasHeader('X-user-id') AND !$request->hasHeader('X-ip-address')) {
			$sessions = Session::where('user_id', $request->header('X-user-id'))->get();

			if ($sessions == null) {
				$medias = Media::where([['for_youth', $for_youth], ['type_id', $type_id]])->orderByDesc('created_at')->get();

                return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));

            } else {
                $session_medias = Media::whereHas('sessions', function ($query) use ($request) {
                                    $query->where('sessions.user_id', $request->header('X-user-id'));
                                })->where([['medias.for_youth', $for_youth], ['medias.type_id', $type_id]])->orderByDesc('medias.created_at')->get();
				$global_medias = Media::where([['for_youth', $for_youth], ['type_id', $type_id]])->orderByDesc('created_at')->get();
                // Merged data
                $medias = ($session_medias->merge($global_medias))->unique();

                return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));
            }

        } else if ($request->hasHeader('X-ip-address')) {
			$sessions = Session::where('ip_address', $request->header('X-ip-address'))->get();

			if ($sessions == null) {
				$medias = Media::where([['for_youth', $for_youth], ['type_id', $type_id]])->orderByDesc('created_at')->get();

                return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));

            } else {
                $session_medias = Media::whereHas('sessions', function ($query) use ($request) {
                                    $query->where('sessions.ip_address', $request->header('X-ip-address'));
                                })->where([['medias.for_youth', $for_youth], ['medias.type_id', $type_id]])->orderByDesc('medias.created_at')->get();
				$global_medias = Media::where([['for_youth', $for_youth], ['type_id', $type_id]])->orderByDesc('created_at')->get();
                // Merged data
                $medias = ($session_medias->merge($global_medias))->unique();

                return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));
            }

        } else {
            $medias = Media::where([['for_youth', $for_youth], ['type_id', $type_id]])->get();

            return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));
        }
    }

    /**
     * Find media views.
     *
     * @param  int  $media_id
     * @return \Illuminate\Http\Response
     */
    public function findViews($media_id)
    {
        $media = Media::find($media_id);

        if (is_null($media)) {
            return $this->handleError(__('notifications.find_media_404'));
        }

        $sessions = Session::whereHas('medias', function($query) {
                            $query->where('media_session.is_viewed', 1)
                                    ->orderByDesc('media_session.created_at');
                        })->get();

        return $this->handleResponse(ResourcesSession::collection($sessions), __('notifications.find_all_sessions_success'));
    }

    /**
     * Find media likes.
     *
     * @param  int  $media_id
     * @return \Illuminate\Http\Response
     */
    public function findLikes($media_id)
    {
        $media = Media::find($media_id);

        if (is_null($media)) {
            return $this->handleError(__('notifications.find_media_404'));
        }

        $users = User::whereHas('medias', function($query) {
                        $query->where('media_user.is_liked', 1)
                                ->orderByDesc('media_user.created_at');
                    })->get();

        return $this->handleResponse(ResourcesUser::collection($users), __('notifications.find_all_users_success'));
    }

    /**
     * Filter medias by categories.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $for_youth
     * @return \Illuminate\Http\Response
     */
    public function filterByCategories(Request $request, $for_youth)
    {
        $medias = Media::whereHas('categories', function($query) use($request) {
                            $query->whereIn('categories.id', $request->categories_ids);
                        })->where('for_youth', $for_youth)->whereNotNull('belongs_to')->orderByDesc('medias.created_at')->get();

        return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'));
    }

    /**
     * Switch the media view.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $media_id
     * @return \Illuminate\Http\Response
     */
    public function switchView(Request $request, $media_id)
    {
        $media = Media::find($media_id);

        if (is_null($media)) {
            return $this->handleError(__('notifications.find_media_404'));
        }

        if (!empty($request->user_id) AND !empty($request->ip_address) OR !empty($request->user_id) AND empty($request->ip_address)) {
            $session = Session::where('user_id', $request->user_id)->first();

            if (!empty($session)) {
                if ($session->medias() == null) {
                    $session->medias()->attach([$media->id => ['is_viewed' => 1]]);
                }

                if ($session->medias() != null) {
                    foreach ($session->medias as $med) {
                        $session->medias()->sync([$media->id => ['is_viewed' => ($med->pivot->is_viewed == 1 ? 0 : 1)]]);
                    }
                }

                if ($media->user_id != null) {
                    $status_unread = Status::where('status_name->fr', 'Non lue')->first();
                    $visitor = User::find($request->user_id);
    
                    /*
                        HISTORY AND/OR NOTIFICATION MANAGEMENT
                    */
                    if (!empty($visitor)) {
                        Notification::create([
                            'notification_url' => 'members/' . $visitor->id,
                            'notification_content' => [
                                'en' => $visitor->firstname . ' watched your ' . $media->type->type_name->en . '.',
                                'fr' => $visitor->firstname . ' a regardé votre ' . $media->type->type_name->fr . '.',
                                'ln' => $visitor->firstname . ' atali ' . $media->type->type_name->ln . ' na yo.',
                            ],
                            'icon' => 'bi bi-eye',
                            'color' => 'text-warning',
                            'status_id' => $status_unread->id,
                            'user_id' => $media->user_id
                        ]);
                    }
                }
            }
        }

        if (empty($request->user_id) AND !empty($request->ip_address)) {
            $session = Session::where('ip_address', $request->ip_address)->first();

            if (!empty($session)) {
                if ($session->medias() == null) {
                    $session->medias()->attach([$media->id => ['is_viewed' => 1]]);
                }

                if ($session->medias() != null) {
                    foreach ($session->medias as $med) {
                        $session->medias()->sync([$media->id => ['is_viewed' => ($med->pivot->is_viewed == 1 ? 0 : 1)]]);
                    }
                }
            }
        }
    }

    /**
     * Switch the media like.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $user_id
     * @param  int $media_id
     * @return \Illuminate\Http\Response
     */
    public function switchLike(Request $request, $user_id, $media_id)
    {
        $user = User::find($user_id);
        $media = Media::find($media_id);

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        if (is_null($media)) {
            return $this->handleError(__('notifications.find_media_404'));
        }

        foreach ($user->medias as $med) {
            if ($med->pivot->media_id == null) {
                $user->medias()->attach([$media->id => ['is_liked' => 1]]);
            }

            if ($med->pivot->media_id != null) {
                $user->medias()->updateExistingPivot([$media->id => ['is_liked' => ($med->pivot->is_liked == 1 ? 0 : 1)]]);
            }
        }

        if ($media->user_id != null) {
            $status_unread = Status::where('status_name->fr', 'Non lue')->first();
            $visitor = User::find($request->header('X-user-id'));

            /*
                HISTORY AND/OR NOTIFICATION MANAGEMENT
            */
            if (!empty($visitor)) {
                Notification::create([
                    'notification_url' => 'members/' . $visitor->id,
                    'notification_content' => [
                        'en' => $visitor->firstname . ' liked your ' . $media->type->type_name->en . '.',
                        'fr' => $visitor->firstname . ' a aimé votre ' . $media->type->type_name->fr . '.',
                        'ln' => $visitor->firstname . ' alingi ' . $media->type->type_name->ln . ' na yo.',
                    ],
                    'icon' => 'bi bi-eye',
                    'color' => 'text-warning',
                    'status_id' => $status_unread->id,
                    'user_id' => $media->user_id
                ]);
            }
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
        $media = Media::find($media_id);

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        if (is_null($media)) {
            return $this->handleError(__('notifications.find_media_404'));
        }

        foreach ($user->medias as $med) {
            if ($med->pivot->media_id == null) {
                $user->medias()->attach([$media->id => [
                    'status_id' => $status_id
                ]]);
            }

            if ($med->pivot->media_id != null) {
                $user->medias()->updateExistingPivot([$media->id => [
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
