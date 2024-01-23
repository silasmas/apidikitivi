<?php

namespace App\Http\Controllers\API;

use App\Models\Group;
use Illuminate\Http\Request;
use App\Http\Resources\Group as ResourcesGroup;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class GroupController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $groups = Group::all();

        return $this->handleResponse(ResourcesGroup::collection($groups), __('notifications.find_all_groups_success'));
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
            'group_name' => [
                'en' => $request->group_name_en,
                'fr' => $request->group_name_fr,
                'ln' => $request->group_name_ln
            ],
            'group_description' => $request->group_description
        ];
        // Select all groups to check unique constraint
        $groups = Group::all();

        // Validate required fields
        if (trim($inputs['group_name']['en']) == null AND trim($inputs['group_name']['fr']) == null AND trim($inputs['group_name']['ln']) == null) {
            return $this->handleError($inputs['group_name'], __('validation.required'), 400);
        }

        // Check if group name already exists
        foreach ($groups as $another_group):
            if ($another_group->group_name->en == $inputs['group_name']) {
                return $this->handleError($inputs['group_name'], __('validation.custom.group_name.exists'), 400);
            }
        endforeach;

        $group = Group::create($inputs);

        return $this->handleResponse(new ResourcesGroup($group), __('notifications.create_group_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $group = Group::find($id);

        if (is_null($group)) {
            return $this->handleError(__('notifications.find_group_404'));
        }

        return $this->handleResponse(new ResourcesGroup($group), __('notifications.find_group_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Group $group)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'group_name' => [
                'en' => $request->group_name_en,
                'fr' => $request->group_name_fr,
                'ln' => $request->group_name_ln
            ],
            'group_description' => $request->group_description
        ];
        // Select all groups and specific group to check unique constraint
        $groups = Group::all();
        $current_group = Group::find($inputs['id']);

        if ($inputs['group_name']['en'] != null) {
            foreach ($groups as $another_group):
                if ($current_group->group_name->en != $inputs['group_name']['en']) {
                    if ($another_group->group_name->en == $inputs['group_name']['en']) {
                        return $this->handleError($inputs['group_name']['en'], __('validation.custom.group_name.exists'), 400);
                    }
                }
            endforeach;

            $group->update([
                'group_name' => [
                    'en' => $request->group_name_en
                ],
                'updated_at' => now()
            ]);
        }

        if ($inputs['group_name']['fr'] != null) {
            foreach ($groups as $another_group):
                if ($current_group->group_name->fr != $inputs['group_name']['fr']) {
                    if ($another_group->group_name->fr == $inputs['group_name']['fr']) {
                        return $this->handleError($inputs['group_name']['fr'], __('validation.custom.group_name.exists'), 400);
                    }
                }
            endforeach;

            $group->update([
                'group_name' => [
                    'fr' => $request->group_name_fr
                ],
                'updated_at' => now()
            ]);
        }

        if ($inputs['group_name']['ln'] != null) {
            foreach ($groups as $another_group):
                if ($current_group->group_name->ln != $inputs['group_name']['ln']) {
                    if ($another_group->group_name->ln == $inputs['group_name']['ln']) {
                        return $this->handleError($inputs['group_name']['ln'], __('validation.custom.group_name.exists'), 400);
                    }
                }
            endforeach;

            $group->update([
                'group_name' => [
                    'ln' => $request->group_name_ln
                ],
                'updated_at' => now()
            ]);
        }

        if ($inputs['group_description'] != null) {
            $group->update([
                'group_description' => $request->group_description,
                'updated_at' => now(),
            ]);
        }

        $group->update($inputs);

        return $this->handleResponse(new ResourcesGroup($group), __('notifications.update_group_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function destroy(Group $group)
    {
        $group->delete();

        $groups = Group::all();

        return $this->handleResponse(ResourcesGroup::collection($groups), __('notifications.delete_group_success'));
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Search a group by its name.
     *
     * @param  string $locale
     * @param  string $data
     * @return \Illuminate\Http\Response
     */
    public function search($locale, $data)
    {
        $groups = Group::where('group_name->' . $locale, 'LIKE', '%' . $data . '%')->get();

        return $this->handleResponse(ResourcesGroup::collection($groups), __('notifications.find_all_groups_success'));
    }
}
