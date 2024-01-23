<?php

namespace App\Http\Controllers\API;

use App\Models\Part;
use Illuminate\Http\Request;
use App\Http\Resources\Part as ResourcesPart;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class PartController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $parts = Part::all();

        return $this->handleResponse(ResourcesPart::collection($parts), __('notifications.find_all_parts_success'));
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
            'part_title' => $request->part_title,
            'part_url' => $request->part_url,
            'media_id' => $request->media_id
        ];
        // Select all media parts to check unique constraint
        $parts = Part::where('media_id', $inputs['media_id'])->get();

        // Validate required fields
        if (trim($inputs['part_title']) == null) {
            return $this->handleError($inputs['part_title'], __('validation.required'), 400);
        }

        if (trim($inputs['part_url']) == null) {
            return $this->handleError($inputs['part_url'], __('validation.required'), 400);
        }

        // Check if part title already exists
        foreach ($parts as $another_part):
            if ($another_part->part_title == $inputs['part_title']) {
                return $this->handleError($inputs['part_title'], __('validation.custom.title.exists'), 400);
            }
        endforeach;

        $part = Part::create($inputs);

        return $this->handleResponse(new ResourcesPart($part), __('notifications.create_part_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $part = Part::find($id);

        if (is_null($part)) {
            return $this->handleError(__('notifications.find_part_404'));
        }

        return $this->handleResponse(new ResourcesPart($part), __('notifications.find_part_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Part  $part
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Part $part)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'part_title' => $request->part_title,
            'part_url' => $request->part_url,
            'media_id' => $request->media_id
        ];
        // Select all media parts to check unique constraint
        $parts = Part::where('media_id', $inputs['media_id'])->get();
        $current_part = Part::find($inputs['id']);

        if ($inputs['part_title'] != null) {
            foreach ($parts as $another_part):
                if ($current_part->part_title != $inputs['part_title']) {
                    if ($another_part->part_title == $inputs['part_title']) {
                        return $this->handleError($inputs['part_title'], __('validation.custom.title.exists'), 400);
                    }
                }
            endforeach;

            $part->update([
                'part_title' => $request->part_title,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['part_url'] != null) {
            $part->update([
                'part_url' => $request->part_url,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['media_id'] != null) {
            $part->update([
                'media_id' => $request->media_id,
                'updated_at' => now(),
            ]);
        }

        return $this->handleResponse(new ResourcesPart($part), __('notifications.update_part_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Part  $part
     * @return \Illuminate\Http\Response
     */
    public function destroy(Part $part)
    {
        $part->delete();

        $parts = Part::all();

        return $this->handleResponse(ResourcesPart::collection($parts), __('notifications.delete_part_success'));
    }
}
