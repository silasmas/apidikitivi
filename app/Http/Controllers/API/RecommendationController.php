<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\Media as ResourcesMedia;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Server side signals for the recommendation engine of the apps.
 * The scoring itself (même série, fréquent, progression dans la foi, diversifier) stays in the app.
 */
class RecommendationController extends BaseController
{
    private const MIN_RESULTS = 6;

    /**
     * "Commun" : what comparable profiles (same age, ± 2 years) watched the most during the last 90 days.
     * Falls back to every profile when there is not enough data for the age group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function common(Request $request)
    {
        $limit = min(max((int) $request->query('limit', 20), 1), 50);
        $for_youth = (int) $request->query('for_youth', 0) === 1;
        $birth_date = $request->user()->birth_date;

        $ids = !empty($birth_date) ? $this->popularMediaIds($limit, $for_youth, $birth_date) : [];

        if (count($ids) < self::MIN_RESULTS) {
            $ids = array_values(array_unique(array_merge($ids, $this->popularMediaIds($limit, $for_youth, null))));
        }

        $medias = Media::with(['type', 'categories'])->whereIn('id', $ids)->get()->sortBy(fn ($media) => array_search($media->id, $ids))->values();

        return $this->handleResponse(ResourcesMedia::collection($medias), __('notifications.find_all_medias_success'), null, count($medias));
    }

    private function popularMediaIds(int $limit, bool $for_youth, ?string $birth_date): array
    {
        $query = DB::table('watch_progress')
                    ->join('medias', 'medias.id', '=', 'watch_progress.media_id')
                    ->join('users', 'users.id', '=', 'watch_progress.user_id')
                    ->where('medias.is_public', 1)
                    ->where('watch_progress.last_watched_at', '>=', now()->subDays(90));

        if ($for_youth) {
            $query->where('medias.for_youth', 1);
        }

        if (!is_null($birth_date)) {
            $query->whereBetween('users.birth_date', [
                date('Y-m-d', strtotime($birth_date . ' -2 years')),
                date('Y-m-d', strtotime($birth_date . ' +2 years')),
            ]);
        }

        return $query->select('watch_progress.media_id', DB::raw('COUNT(DISTINCT watch_progress.user_id) AS viewers'), DB::raw('SUM(watch_progress.completed) AS completions'))
                    ->groupBy('watch_progress.media_id')
                    ->orderByDesc('viewers')
                    ->orderByDesc('completions')
                    ->limit($limit)
                    ->pluck('watch_progress.media_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
    }
}
