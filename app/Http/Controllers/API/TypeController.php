<?php

namespace App\Http\Controllers\API;

use App\Models\Type;
use Illuminate\Http\Request;
use App\Http\Resources\Type as ResourcesType;
use App\Models\Group;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class TypeController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $types = Type::all();

        return $this->handleResponse(ResourcesType::collection($types), __('notifications.find_all_types_success'));
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
            'type_name' => [
                'en' => $request->type_name_en,
                'fr' => $request->type_name_fr,
                'ln' => $request->type_name_ln
            ],
            'type_description' => $request->type_description,
            'icon' => $request->icon,
            'color' => $request->color,
            'group_id' => $request->group_id
        ];
        // Select all group types to check unique constraint
        $types = Type::where('group_id', $inputs['group_id'])->get();

        // Validate required fields
        if (trim($inputs['type_name']) == null) {
            return $this->handleError($inputs['type_name'], __('validation.required'), 400);
        }

        if ($inputs['group_id'] == null OR $inputs['group_id'] == ' ') {
            return $this->handleError($inputs['group_id'], __('validation.required'), 400);
        }

        // Check if type name already exists
        foreach ($types as $another_type):
            if ($another_type->type_name == $inputs['type_name']) {
                return $this->handleError($inputs['type_name'], __('validation.custom.type_name.exists'), 400);
            }
        endforeach;

        $type = Type::create($inputs);

        return $this->handleResponse(new ResourcesType($type), __('notifications.create_type_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $type = Type::find($id);

        if (is_null($type)) {
            return $this->handleError(__('notifications.find_type_404'));
        }

        return $this->handleResponse(new ResourcesType($type), __('notifications.find_type_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Type  $type
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Type $type)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'type_name' => [
                'en' => $request->type_name_en,
                'fr' => $request->type_name_fr,
                'ln' => $request->type_name_ln
            ],
            'type_description' => $request->type_description,
            'icon' => $request->icon,
            'color' => $request->color,
            'group_id' => $request->group_id
        ];
        // Select all group types and specific type to check unique constraint
        $types = Type::where('group_id', $inputs['group_id'])->get();
        $current_type = Type::find($inputs['id']);

        if ($inputs['type_name'] != null) {
            foreach ($types as $another_type):
                if ($current_type->type_name != $inputs['type_name']) {
                    if ($another_type->type_name == $inputs['type_name']) {
                        return $this->handleError($inputs['type_name'], __('validation.custom.type_name.exists'), 400);
                    }
                }
            endforeach;

            $type->update([
                'type_name' => [
                    'en' => $request->type_name_en,
                    'fr' => $request->type_name_fr,
                    'ln' => $request->type_name_ln
                ],
                'updated_at' => now()
            ]);
        }

        if ($inputs['type_description'] != null) {
            $type->update([
                'type_description' => $request->type_description,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['icon'] != null) {
            $type->update([
                'icon' => $request->icon,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['color'] != null) {
            $type->update([
                'color' => $request->color,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['group_id'] != null) {
            $type->update([
                'group_id' => $request->group_id,
                'updated_at' => now(),
            ]);
        }

        $type->update($inputs);

        return $this->handleResponse(new ResourcesType($type), __('notifications.update_type_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Type  $type
     * @return \Illuminate\Http\Response
     */
    public function destroy(Type $type)
    {
        $type->delete();

        $types = Type::all();

        return $this->handleResponse(ResourcesType::collection($types), __('notifications.delete_type_success'));
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Search a type by its name.
     *
     * @param  string $locale
     * @param  string $data
     * @return \Illuminate\Http\Response
     */
    public function search($locale, $data)
    {
        $type = Type::where('type_name->' . $locale, $data)->first();

        if (is_null($type)) {
            return $this->handleError(__('notifications.find_type_404'));
        }

        return $this->handleResponse(new ResourcesType($type), __('notifications.find_type_success'));
    }

    /**
     * Find all type by group.
     *
     * @param  string $locale
     * @param  string $group_name
     * @return \Illuminate\Http\Response
     */
    public function findByGroup($locale, $group_name)
    {
        $group = Group::where('group_name->' . $locale, $group_name)->first();

        if (is_null($group)) {
            return $this->handleError(__('notifications.find_group_404'));
        }

        $types = Type::where('group_id', $group->id)->get();

        return $this->handleResponse(ResourcesType::collection($types), __('notifications.find_all_types_success'));
    }
}
