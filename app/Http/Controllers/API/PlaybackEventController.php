<?php

namespace App\Http\Controllers\API;

use App\Models\PlaybackEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Playback and performance logs sent by the apps (visitors included).
 */
class PlaybackEventController extends BaseController
{
    private const MAX_BATCH = 100;

    /**
     * Receive a batch of events : { platform, app_version, device_class, device_id, events: [{ event, at, props }] }
     * The user is optional (Bearer token if connected).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function batch(Request $request)
    {
        $user = auth('sanctum')->user();
        $common = [
            'user_id' => $user?->id,
            'platform' => $this->short($request->platform, 20),
            'device_class' => $this->short($request->device_class, 10),
            'app_version' => $this->short($request->app_version, 20),
            'device_id' => $this->short($request->device_id, 64),
            'created_at' => now(),
        ];
        $rows = [];

        foreach (collect(is_array($request->events) ? $request->events : [])->take(self::MAX_BATCH) as $item) {
            if (!is_array($item) or !in_array($item['event'] ?? null, PlaybackEvent::EVENTS, true)) {
                continue;
            }

            $props = is_array($item['props'] ?? null) ? $item['props'] : [];
            $media_id = $props['media_id'] ?? $props['id'] ?? null;
            $value_ms = $props['ms'] ?? $props['value_ms'] ?? null;

            unset($props['media_id'], $props['id'], $props['ms'], $props['value_ms']);

            $rows[] = array_merge($common, [
                'event' => $item['event'],
                'media_id' => is_numeric($media_id) ? (int) $media_id : null,
                'value_ms' => is_numeric($value_ms) ? min(max((int) $value_ms, 0), 4294967295) : null,
                'props' => empty($props) ? null : json_encode($props, JSON_UNESCAPED_UNICODE),
                'occurred_at' => $this->occurredAt($item['at'] ?? null),
            ]);
        }

        if (!empty($rows)) {
            PlaybackEvent::insert($rows);
        }

        return $this->handleResponse(['received' => count($rows)], __('notifications.events_received'));
    }

    /**
     * Dashboard figures over a period (default : last 7 days). Admin roles only.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function stats(Request $request)
    {
        if (!$request->user()->roles()->whereIn('role_name', ['Administrateur', 'Contrôleur'])->exists()) {
            return $this->handleError(__('notifications.only_admin_allowed'), [], 403);
        }

        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->subDays(7)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();
        $period = PlaybackEvent::whereBetween('occurred_at', [$from, $to]);

        $counts = (clone $period)->select('event', DB::raw('COUNT(*) AS total'))->groupBy('event')->pluck('total', 'event');
        $timings = (clone $period)->whereIn('event', ['video_startup_ms', 'thumbnail_load_ms'])->whereNotNull('value_ms')
                                    ->select('event', DB::raw('ROUND(AVG(value_ms)) AS avg_ms'), DB::raw('MAX(value_ms) AS max_ms'), DB::raw('COUNT(*) AS samples'))
                                    ->groupBy('event')->get()->keyBy('event');
        $plays = (int) ($counts['video_play'] ?? 0);
        $top_errors = (clone $period)->whereIn('event', ['playback_error', 'buffer_start'])->whereNotNull('media_id')
                                        ->select('media_id', DB::raw('COUNT(*) AS total'))->groupBy('media_id')->orderByDesc('total')->limit(10)
                                        ->with('media:id,media_title')->get()
                                        ->map(fn ($row) => ['media_id' => $row->media_id, 'media_title' => $row->media?->media_title, 'total' => (int) $row->total]);

        return $this->handleResponse([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'counts' => $counts,
            'completion_rate' => $plays > 0 ? round(((int) ($counts['video_complete'] ?? 0)) / $plays, 3) : null,
            'abandon_rate' => $plays > 0 ? round(((int) ($counts['video_abandon'] ?? 0)) / $plays, 3) : null,
            'timings' => $timings,
            'top_problem_medias' => $top_errors,
        ], __('notifications.find_stats_success'));
    }

    private function short($value, int $length): ?string
    {
        return is_scalar($value) && $value !== '' ? mb_substr((string) $value, 0, $length) : null;
    }

    private function occurredAt($value): Carbon
    {
        if (!is_numeric($value)) {
            return now();
        }

        $date = Carbon::createFromTimestampMs((int) $value);

        // horloge d'appareil incohérente : on garde l'heure de réception
        return $date->greaterThan(now()->addMinutes(5)) || $date->lessThan(now()->subDays(30)) ? now() : $date;
    }
}
