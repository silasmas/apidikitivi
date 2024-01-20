<?php

namespace App\Http\Controllers\API;

use App\Models\LegalInfoSubject;
use Illuminate\Http\Request;
use App\Http\Resources\LegalInfoSubject as ResourcesLegalInfoSubject;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class LegalInfoSubjectController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $legal_info_subjects = LegalInfoSubject::orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesLegalInfoSubject::collection($legal_info_subjects), __('notifications.find_all_legal_info_subjects_success'));
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
            'subject_name' => $request->subject_name,
            'subject_description' => $request->subject_description,
            'status_id' => $request->status_id
        ];

        // Validate required fields
        if ($inputs['subject_name'] == null OR $inputs['subject_name'] == ' ') {
            return $this->handleError($inputs['subject_name'], __('validation.required'), 400);
        }

        if ($inputs['status_id'] == null OR $inputs['status_id'] == ' ') {
            return $this->handleError($inputs['status_id'], __('validation.required'), 400);
        }

        $legal_info_subject = LegalInfoSubject::create($inputs);

        return $this->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.create_legal_info_subject_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $legal_info_subject = LegalInfoSubject::find($id);

        if (is_null($legal_info_subject)) {
            return $this->handleError(__('notifications.find_legal_info_subject_404'));
        }

        return $this->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.find_legal_info_subject_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\LegalInfoSubject  $legal_info_subject
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, LegalInfoSubject $legal_info_subject)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'subject_name' => $request->subject_name,
            'subject_description' => $request->subject_description,
            'status_id' => $request->status_id,
            'updated_at' => now()
        ];

        if ($inputs['subject_name'] == null OR $inputs['subject_name'] == ' ') {
            return $this->handleError($inputs['subject_name'], __('validation.required'), 400);
        }

        if ($inputs['status_id'] == null OR $inputs['status_id'] == ' ') {
            return $this->handleError($inputs['status_id'], __('validation.required'), 400);
        }

        $legal_info_subject->update($inputs);

        return $this->handleResponse(new ResourcesLegalInfoSubject($legal_info_subject), __('notifications.update_legal_info_subject_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\LegalInfoSubject  $legal_info_subject
     * @return \Illuminate\Http\Response
     */
    public function destroy(LegalInfoSubject $legal_info_subject)
    {
        $legal_info_subject->delete();

        $legal_info_subjects = LegalInfoSubject::all();

        return $this->handleResponse(ResourcesLegalInfoSubject::collection($legal_info_subjects), __('notifications.delete_legal_info_subject_success'));
    }
}
