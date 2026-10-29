<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\ContentFilter as ResourcesContentFilter;
use App\Models\ContentFilter;

/**
 * "Jeunesse" filters by Dikitivi content type (Bloc 2), managed from the dashboard.
 */
class ContentFilterController extends BaseController
{
    /**
     * Active filters, in display order ("Tous" is added by the app).
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $filters = ContentFilter::where('is_active', 1)->orderBy('position')->orderBy('id')->get();

        return $this->handleResponse(ResourcesContentFilter::collection($filters), __('notifications.find_content_filters_success'), null, count($filters));
    }
}
