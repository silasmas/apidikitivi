<?php

namespace App\Http\Controllers\API;

use App\Models\LegalInfoTitle;
use Illuminate\Http\Request;
use App\Http\Resources\LegalInfoTitle as ResourcesLegalInfoTitle;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class LegalInfoTitleController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $legal_info_titles = LegalInfoTitle::all();

        return $this->handleResponse(ResourcesLegalInfoTitle::collection($legal_info_titles), __('notifications.find_all_legal_info_titles_success'));
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
            'title' => [
                'en' => $request->title_en,
                'fr' => $request->title_fr,
                'ln' => $request->title_ln
            ],
            'legal_info_subject_id' => $request->legal_info_subject_id
        ];
        // Select all titles of a same subject to check unique constraint
        $legal_info_titles = LegalInfoTitle::where('legal_info_subject_id', $inputs['legal_info_subject_id'])->get();

        // Validate required fields
        if ($inputs['legal_info_subject_id'] == null OR $inputs['legal_info_subject_id'] == ' ') {
            return $this->handleError($inputs['legal_info_subject_id'], __('validation.required'), 400);
        }

        // Check if title already exists
        foreach ($legal_info_titles as $another_legal_info_title):
            if ($another_legal_info_title->title == $inputs['title']) {
                return $this->handleError($inputs['title'], __('validation.custom.title.exists'), 400);
            }
        endforeach;

        $legal_info_title = LegalInfoTitle::create($inputs);

        return $this->handleResponse(new ResourcesLegalInfoTitle($legal_info_title), __('notifications.create_legal_info_title_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $legal_info_title = LegalInfoTitle::find($id);

        if (is_null($legal_info_title)) {
            return $this->handleError(__('notifications.find_legal_info_title_404'));
        }

        return $this->handleResponse(new ResourcesLegalInfoTitle($legal_info_title), __('notifications.find_legal_info_title_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\LegalInfoTitle  $legal_info_title
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LegalInfoTitle $legal_info_title)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'title' => [
                'en' => $request->title_en,
                'fr' => $request->title_fr,
                'ln' => $request->title_ln
            ],
            'legal_info_subject_id' => $request->legal_info_subject_id
        ];
        // Select all titles of a same subject and current title to check unique constraint
        $legal_info_titles = LegalInfoTitle::where('legal_info_subject_id', $inputs['legal_info_subject_id'])->get();
        $current_legal_info_title = LegalInfoTitle::find($inputs['id']);

        if ($inputs['title']['en'] != null) {
            foreach ($legal_info_titles as $another_legal_info_title):
                if ($current_legal_info_title->title->en != $inputs['title']['en']) {
                    if ($another_legal_info_title->title->en == $inputs['title']['en']) {
                        return $this->handleError($inputs['title']['en'], __('validation.custom.title.exists'), 400);
                    }
                }
            endforeach;
    
            $legal_info_title->update([
                'title' => [
                    'en' => $request->title_en
                ],
                'updated_at' => now()
            ]);
        }

        if ($inputs['title']['fr'] != null) {
            foreach ($legal_info_titles as $another_legal_info_title):
                if ($current_legal_info_title->title->fr != $inputs['title']['fr']) {
                    if ($another_legal_info_title->title->fr == $inputs['title']['fr']) {
                        return $this->handleError($inputs['title']['fr'], __('validation.custom.title.exists'), 400);
                    }
                }
            endforeach;
    
            $legal_info_title->update([
                'title' => [
                    'fr' => $request->title_fr
                ],
                'updated_at' => now()
            ]);
        }

        if ($inputs['title']['ln'] != null) {
            foreach ($legal_info_titles as $another_legal_info_title):
                if ($current_legal_info_title->title->ln != $inputs['title']['ln']) {
                    if ($another_legal_info_title->title->ln == $inputs['title']['ln']) {
                        return $this->handleError($inputs['title']['ln'], __('validation.custom.title.exists'), 400);
                    }
                }
            endforeach;
    
            $legal_info_title->update([
                'title' => [
                    'ln' => $request->title_ln
                ],
                'updated_at' => now()
            ]);
        }

        return $this->handleResponse(new ResourcesLegalInfoTitle($legal_info_title), __('notifications.update_legal_info_title_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\LegalInfoTitle  $legal_info_title
     * @return \Illuminate\Http\Response
     */
    public function destroy(LegalInfoTitle $legal_info_title)
    {
        $legal_info_title->delete();

        $legal_info_titles = LegalInfoTitle::all();

        return $this->handleResponse(ResourcesLegalInfoTitle::collection($legal_info_titles), __('notifications.delete_legal_info_title_success'));
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Search a title of subject about the app operation by its name.
     *
     * @param  string $locale
     * @param  string $data
     * @return \Illuminate\Http\Response
     */
    public function search($locale, $data)
    {
        $legal_info_titles = LegalInfoTitle::where('title->' . $locale, 'LIKE', '%' . $data . '%')->get();

        return $this->handleResponse(ResourcesLegalInfoTitle::collection($legal_info_titles), __('notifications.find_all_legal_info_titles_success'));
    }
}
