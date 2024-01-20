<?php

namespace App\Http\Controllers\API;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Resources\Role as ResourcesRole;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class RoleController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $roles = Role::all();

        return $this->handleResponse(ResourcesRole::collection($roles), __('notifications.find_all_roles_success'));
    }

    /**
     * Store a resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Get inputs
        $inputs = [
            'role_name' => $request->role_name,
            'role_description' => $request->role_description
        ];
        // Select all roles to check unique constraint
        $roles = Role::all();

        // Validate required fields
        if ($inputs['role_name'] == null OR $inputs['role_name'] == ' ') {
            return $this->handleError($inputs['role_name'], __('validation.required'), 400);
        }

        // Check if role name already exists
        foreach ($roles as $another_role):
            if ($another_role->role_name == $inputs['role_name']) {
                return $this->handleError($inputs['role_name'], __('validation.custom.role_name.exists'), 400);
            }
        endforeach;

        $role = Role::create($inputs);

        return $this->handleResponse(new ResourcesRole($role), __('notifications.create_role_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $role = Role::find($id);

        if (is_null($role)) {
            return $this->handleError(__('notifications.find_role_404'));
        }

        return $this->handleResponse(new ResourcesRole($role), __('notifications.find_role_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'role_name' => $request->role_name,
            'role_description' => $request->role_description,
            'updated_at' => now()
        ];
        // Select all roles and specific role to check unique constraint
        $roles = Role::all();
        $current_role = Role::find($inputs['id']);

        if ($inputs['role_name'] == null OR $inputs['role_name'] == ' ') {
            return $this->handleError($inputs['role_name'], __('validation.required'), 400);
        }

        foreach ($roles as $another_role):
            if ($current_role->role_name != $inputs['role_name']) {
                if ($another_role->role_name == $inputs['role_name']) {
                    return $this->handleError($inputs['role_name'], __('validation.custom.role_name.exists'), 400);
                }
            }
        endforeach;

        $role->update($inputs);

        return $this->handleResponse(new ResourcesRole($role), __('notifications.update_role_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        $role->delete();

        $roles = Role::all();

        return $this->handleResponse(ResourcesRole::collection($roles), __('notifications.delete_role_success'));
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Search a role by its name.
     *
     * @param  string $data
     * @return \Illuminate\Http\Response
     */
    public function search($data)
    {
        $roles = Role::where('role_name', $data)->get();

        return $this->handleResponse(ResourcesRole::collection($roles), __('notifications.find_all_roles_success'));
    }
}
