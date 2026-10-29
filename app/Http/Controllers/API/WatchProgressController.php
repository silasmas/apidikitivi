<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\WatchProgress as ResourcesWatchProgress;
use App\Models\Media;
use App\Models\WatchProgress;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Playback history of the connected profile : resume, completed contents, frequency.
 * Same rules as the app : a content watched at 95 % or more is "completed" and restarts from 0.
 */
class WatchProgressController extends BaseController
{
    private const COMPLETION_RATIO = 0.95;

    private const MAX_SYNC_ITEMS = 200;

    /**
     * History of the connected profile, most recent first.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $limit = min(max((int) $request->query('limit', 100), 1), 300);
        $history = WatchProgress::with(['media.type', 'media.categories'])
                                    ->where('user_id', $request->user()->id)
                                    ->whereHas('media', function ($query) {$query->where('is_public', 1);})
                                    ->orderByDesc('last_watched_at')
                                    ->limit($limit)
                                    ->get();

        return $this->handleResponse(ResourcesWatchProgress::collection($history), __('notifications.find_history_success'), null, count($history));
    }

    /**
     * Save the position of a media for the connected profile.
     * "started=1" counts a new play (frequency signal of the recommendations).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $media_id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $media_id)
    {
        $media = Media::find($media_id);

        if (is_null($media)) {
            return $this->handleError(__('notifications.find_media_404'));
        }

        $progress = WatchProgress::firstOrNew(['user_id' => $request->user()->id, 'media_id' => $media->id]);
        $position = max(0, (int) $request->input('position', $progress->position_seconds ?? 0));
        $duration = max(0, (int) $request->input('duration', $progress->duration_seconds ?? 0));
        $completed = filter_var($request->input('completed', false), FILTER_VALIDATE_BOOLEAN)
                        || ($duration > 0 && $position / $duration >= self::COMPLETION_RATIO);

        $progress->fill([
            'position_seconds' => $completed ? 0 : $position,
            'duration_seconds' => $duration,
            'completed' => $completed || (bool) $progress->completed,
            'play_count' => (int) $progress->play_count + (filter_var($request->input('started', false), FILTER_VALIDATE_BOOLEAN) ? 1 : 0),
            'last_watched_at' => now(),
        ])->save();

        return $this->handleResponse(new ResourcesWatchProgress($progress), __('notifications.update_history_success'));
    }

    /**
     * Merge the local history of the app (offline, or first launch of the v2) with the server.
     * Last write wins for the position ; "completed" and "play_count" never go back.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sync(Request $request)
    {
        $user_id = $request->user()->id;
        $items = collect(is_array($request->items) ? $request->items : [])->take(self::MAX_SYNC_ITEMS);
        $existing_media_ids = Media::whereIn('id', $items->pluck('media_id')->filter()->all())->pluck('id')->all();

        foreach ($items as $item) {
            if (!is_array($item) or !in_array($item['media_id'] ?? null, $existing_media_ids)) {
                continue;
            }

            $updated_at = $this->parseDate($item['updated_at'] ?? null);
            $progress = WatchProgress::firstOrNew(['user_id' => $user_id, 'media_id' => $item['media_id']]);
            $is_newer = !$progress->exists || is_null($progress->last_watched_at) || $updated_at->greaterThan($progress->last_watched_at);
            $completed = filter_var($item['completed'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($is_newer) {
                $progress->position_seconds = $completed ? 0 : max(0, (int) ($item['position'] ?? 0));
                $progress->duration_seconds = max(0, (int) ($item['duration'] ?? $progress->duration_seconds ?? 0));
                $progress->last_watched_at = $updated_at;
            }

            $progress->completed = $completed || (bool) $progress->completed;
            $progress->play_count = max((int) $progress->play_count, (int) ($item['play_count'] ?? 0));
            $progress->save();
        }

        return $this->index($request);
    }

    /**
     * Accepts epoch milliseconds (app) or a date string.
     */
    private function parseDate($value): Carbon
    {
        try {
            $date = is_numeric($value) ? Carbon::createFromTimestampMs((int) $value) : Carbon::parse((string) $value);
        } catch (\Throwable $th) {
            return now();
        }

        // une horloge d'appareil en avance ne doit pas figer l'historique
        return $date->greaterThan(now()) ? now() : $date;
    }
}
