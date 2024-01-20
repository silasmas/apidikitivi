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
            'group_name' => $request->group_name,
            'group_description' => $request->group_description
        ];
        // Select all groups to check unique constraint
        $groups = Group::all();

        // Validate required fields
        if ($inputs['group_name'] == null OR $inputs['group_name'] == ' ') {
            return $this->handleError($inputs['group_name'], __('validation.required'), 400);
        }

        // Check if group name already exists
        foreach ($groups as $another_group):
            if ($another_group->group_name == $inputs['group_name']) {
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
            'group_name' => $request->group_name,
            'group_description' => $request->group_description,
            'updated_at' => now()
        ];
        // Select all groups and specific group to check unique constraint
        $groups = Group::all();
        $current_group = Group::find($inputs['id']);

        if ($inputs['group_name'] == null OR $inputs['group_name'] == ' ') {
            return $this->handleError($inputs['group_name'], __('validation.required'), 400);
        }

        foreach ($groups as $another_group):
            if ($current_group->group_name != $inputs['group_name']) {
                if ($another_group->group_name == $inputs['group_name']) {
                    return $this->handleError($inputs['group_name'], __('validation.custom.group_name.exists'), 400);
                }
            }
        endforeach;

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
     * @param  string $data
     * @return \Illuminate\Http\Response
     */
    public function search($data)
    {
        $groups = Group::where('group_name', $data)->get();

        return $this->handleResponse(ResourcesGroup::collection($groups), __('notifications.find_all_groups_success'));
    }
}
