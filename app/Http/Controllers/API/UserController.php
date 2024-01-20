<?php

namespace App\Http\Controllers\API;

use App\Models\Address;
use App\Models\Image;
use App\Models\Notification;
use App\Models\PasswordReset;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\Status;
use App\Models\User;
use Nette\Utils\Random;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use stdClass;
use App\Http\Resources\User as ResourcesUser;
use App\Http\Resources\PasswordReset as ResourcesPasswordReset;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class UserController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesUser::collection($users), __('notifications.find_all_users_success'));
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
            'national_number' => $request->national_number,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'surname' => $request->surname,
            'gender' => $request->gender,
            'birth_city' => $request->birth_city,
            'birth_date' => $request->birth_date,
            'nationality' => $request->nationality,
            'p_o_box' => $request->p_o_box,
            'email' => $request->email,
            'phone' => $request->phone,
            'email_verified_at' => $request->email_verified_at,
            'password' => $request->password,
            'confirm_password' => $request->confirm_password,
            'remember_token' => $request->remember_token,
            'api_token' => $request->api_token,
            'status_id' => $request->status_id
        ];
        $users = User::all();
        $password_resets = PasswordReset::all();
        // $basic  = new \Vonage\Client\Credentials\Basic('89e3b822', 'cab98aefeaab1434ACR');
        $basic  = new \Vonage\Client\Credentials\Basic('5a4c014d', 'dhOq17USeZadLgIw');
        $client = new \Vonage\Client($basic);

        // Validate required fields
        if ($inputs['email'] == null AND $inputs['phone'] == null) {
            return $this->handleError(__('validation.custom.email_or_phone.required'));
        }

        if ($inputs['email'] == ' ' AND $inputs['phone'] == ' ') {
            return $this->handleError(__('validation.custom.email_or_phone.required'));
        }

        if ($inputs['email'] == null AND $inputs['phone'] == ' ') {
            return $this->handleError(__('validation.custom.email_or_phone.required'));
        }

        if ($inputs['email'] == ' ' AND $inputs['phone'] == null) {
            return $this->handleError(__('validation.custom.email_or_phone.required'));
        }

        if ($inputs['email'] != null) {
            // Check if user email already exists
            foreach ($users as $another_user):
                if ($another_user->email == $inputs['email']) {
                    return $this->handleError($inputs['email'], __('validation.custom.email.exists'), 400);
                }
            endforeach;

            // If email exists in "password_reset" table, delete it
            if ($password_resets != null) {
                foreach ($password_resets as $password_reset):
                    if ($password_reset->email == $inputs['email']) {
                        $password_reset->delete();
                    }
                endforeach;
            }
        }

        if ($inputs['phone'] != null) {
            // Check if user phone already exists
            foreach ($users as $another_user):
                if ($another_user->phone == $inputs['phone']) {
                    return $this->handleError($inputs['phone'], __('validation.custom.phone.exists'), 400);
                }
            endforeach;

            // If phone exists in "password_reset" table, delete it
            if ($password_resets != null) {
                foreach ($password_resets as $password_reset):
                    if ($password_reset->phone == $inputs['phone']) {
                        $password_reset->delete();
                    }
                endforeach;
            }
        }

        if ($inputs['password'] != null) {
            if ($inputs['confirm_password'] != $inputs['password'] OR $inputs['confirm_password'] == null) {
                return $this->handleError($inputs['confirm_password'], __('notifications.confirm_password_error'), 400);
            }

            if (preg_match('#^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$#', $inputs['password']) == 0) {
                return $this->handleError($inputs['password'], __('miscellaneous.password.error'), 400);
            }

            $random_string = (string) random_int(1000000, 9999999);

            if ($inputs['email'] != null AND $inputs['phone'] != null) {
                $password_reset = PasswordReset::create([
                    'email' => $inputs['email'],
                    'phone' => $inputs['phone'],
                    'token' => $random_string,
                    'former_password' => $inputs['password']
                ]);

                try {
                    $client->sms()->send(new \Vonage\SMS\Message\SMS($password_reset->phone, 'ACR', (string) $password_reset->token));

                } catch (\Throwable $th) {
                    return $this->handleError($th->getMessage(), __('notifications.create_user_SMS_failed'), 500);
                }

            } else {
                if ($inputs['email'] != null) {
                    PasswordReset::create([
                        'email' => $inputs['email'],
                        'token' => $random_string,
                        'former_password' => $inputs['password']
                    ]);
                }

                if ($inputs['phone'] != null) {
                    $password_reset = PasswordReset::create([
                        'phone' => $inputs['phone'],
                        'token' => $random_string,
                        'former_password' => $inputs['password']
                    ]);

                    try {
                        $client->sms()->send(new \Vonage\SMS\Message\SMS($password_reset->phone, 'ACR', (string) $password_reset->token));

                    } catch (\Throwable $th) {
                        return $this->handleError($th->getMessage(), __('notifications.create_user_SMS_failed'), 500);
                    }
                }
            }
        }

        if ($inputs['password'] == null) {
            $random_string = (string) random_int(1000000, 9999999);

            if ($inputs['email'] != null AND $inputs['phone'] != null) {
                $password_reset = PasswordReset::create([
                    'email' => $inputs['email'],
                    'phone' => $inputs['phone'],
                    'token' => $random_string,
                    'former_password' => Random::generate(10, 'a-zA-Z'),
                ]);

                $inputs['password'] = Hash::make($password_reset->former_password);

                try {
                    $client->sms()->send(new \Vonage\SMS\Message\SMS($password_reset->phone, 'ACR', (string) $password_reset->token));

                } catch (\Throwable $th) {
                    return $this->handleError($th->getMessage(), __('notifications.create_user_SMS_failed'), 500);
                }

            } else {
                if ($inputs['email'] != null) {
                    $password_reset = PasswordReset::create([
                        'email' => $inputs['email'],
                        'token' => $random_string,
                        'former_password' => Random::generate(10, 'a-zA-Z'),
                    ]);

                    $inputs['password'] = Hash::make($password_reset->former_password);
                }

                if ($inputs['phone'] != null) {
                    $password_reset = PasswordReset::create([
                        'phone' => $inputs['phone'],
                        'token' => $random_string,
                        'former_password' => Random::generate(10, 'a-zA-Z'),
                    ]);

                    $inputs['password'] = Hash::make($password_reset->former_password);

                    try {
                        $client->sms()->send(new \Vonage\SMS\Message\SMS($password_reset->phone, 'ACR', (string) $password_reset->token));

                    } catch (\Throwable $th) {
                        return $this->handleError($th->getMessage(), __('notifications.create_user_SMS_failed'), 500);
                    }
                }
            }
        }

        $user = User::create($inputs);

        if ($user->lastname != null AND $user->birth_date != null) {
            if ($user->national_number == null) {
                $user->update([
                    'national_number' => 'ACR-' . Random::generate(4, '0-9') . '-' . strtoupper(substr($user->lastname, 0, 3)) . '-' . explode('-', $user->birth_date)[0] . '.' . explode('-', $user->birth_date)[1] . '.' . explode('-', $user->birth_date)[2] . '-0000',
                    'updated_at' => now(),
                ]);
            }
        }

        if ($request->role_id != null) {
            RoleUser::create([
                'role_id' => $request->role_id, 
                'user_id' => $user->id
            ]);

            /*
                HISTORY AND/OR NOTIFICATION MANAGEMENT
            */
            $status_activated = Status::where('status_name', 'Activé')->first();
            $status_ongoing = Status::where('status_name', 'En attente')->first();
            $status_unread = Status::where('status_name', 'Non lue')->first();
            $admin_role = Role::where('role_name', 'Administrateur')->first();
            $supporting_member_role = Role::where('role_name', 'Membre Sympathisant')->first();
            $effecive_member_role = Role::where('role_name', 'Membre Effectif')->first();
            $honorary_member_role = Role::where('role_name', 'Membre d\'Honneur')->first();
            $current_role = Role::find($request->role_id);

            // If the new user is a member, send notification to 
            // all managers and a welcome notification to the new user
            if ($current_role->id == $supporting_member_role->id OR $current_role->id == $effecive_member_role->id OR $current_role->id == $honorary_member_role->id) {
                $user->update([
                    'status_id' => $status_ongoing->id,
                    // 'status_id' => $status_activated->id,
                ]);

                $manager_role = Role::where('role_name', 'Manager')->first();
                $role_users = RoleUser::where('role_id', $manager_role->id)->get();

                foreach ($role_users as $executive):
                    Notification::create([
                        'notification_url' => 'members/' . $user->id,
                        'notification_content' => $user->firstname . ' ' . $user->lastname . ' ' . __('notifications.subscribed_to_party'),
                        'status_id' => $status_unread->id,
                        'user_id' => $executive->user_id
                    ]);
                endforeach;

                Notification::create([
                    'notification_url' => 'about/terms_of_use',
                    'notification_content' => __('notifications.welcome_member'),
                    'status_id' => $status_unread->id,
                    'user_id' => $user->id,
                ]);
            }

            // If the new user is neither a member nor an admin, send a ordinary welcome notification
            if ($current_role->id != $supporting_member_role->id AND $current_role->id != $effecive_member_role->id AND $current_role->id != $honorary_member_role->id AND $current_role->id != $admin_role->id) {
                $user->update([
                    'status_id' => $status_activated->id,
                ]);

                Notification::create([
                    'notification_url' => 'about/terms_of_use',
                    'notification_content' => __('notifications.welcome_user'),
                    'status_id' => $status_unread->id,
                    'user_id' => $user->id,
                ]);
            }
        }

        // If user want to add address
        if ($request->address_content != null OR $request->neighborhood != null OR $request->area != null OR $request->city != null) {
            Address::create([
                'address_content' => $request->address_content,
                'address_content_2' => $request->address_content_2,
                'neighborhood' => $request->neighborhood,
                'area' => $request->area,
                'city' => $request->city,
                'type_id' => $request->type_id,
                'country_id' => $request->country_id,
                'user_id' => $user->id
            ]);
        }

        $object = new stdClass();
        $object->password_reset = new ResourcesPasswordReset($password_reset);
        $object->user = new ResourcesUser($user);

        return $this->handleResponse($object, __('notifications.create_user_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::find($id);

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        return $this->handleResponse(new ResourcesUser($user), __('notifications.find_user_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'national_number' => $request->serial_number,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'surname' => $request->surname,
            'gender' => $request->gender,
            'birth_city' => $request->birth_city,
            'birth_date' => $request->birth_date,
            'nationality' => $request->nationality,
            'p_o_box' => $request->p_o_box,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password,
            'confirm_password' => $request->confirm_password
        ];

        $current_user = User::find($inputs['id']);

        if (!is_null($current_user)) {
            if ($current_user->lastname == null OR $current_user->birth_date == null) {
                if ($request->lastname != null AND $request->birth_date != null) {
                    $user->update([
                        'national_number' => 'ACR-' . Random::generate(4, '0-9') . '-' . strtoupper(substr($request->lastname, 0, 3)) . '-' . explode('-', $request->birth_date)[0] . '.' . explode('-', $request->birth_date)[1] . '.' . explode('-', $request->birth_date)[2] . '-0000',
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($current_user->lastname != null AND $current_user->birth_date != null) {
                if ($current_user->national_number == null) {
                    $user->update([
                        'national_number' => 'ACR-' . Random::generate(4, '0-9') . '-' . strtoupper(substr($current_user->lastname, 0, 3)) . '-' . explode('-', $current_user->birth_date)[0] . '.' . explode('-', $current_user->birth_date)[1] . '.' . explode('-', $current_user->birth_date)[2] . '-0000',
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        if ($inputs['firstname'] != null) {
            $user->update([
                'firstname' => $request->firstname,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['lastname'] != null) {
            $user->update([
                'lastname' => $request->lastname,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['surname'] != null) {
            $user->update([
                'surname' => $request->surname,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['gender'] != null) {
            $user->update([
                'gender' => $request->gender,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['birth_city'] != null) {
            $user->update([
                'birth_city' => $request->birth_city,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['birth_date'] != null) {
            $user->update([
                'birth_date' => $request->birth_date,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['nationality'] != null) {
            $user->update([
                'nationality' => $request->nationality,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['p_o_box'] != null) {
            $user->update([
                'p_o_box' => $request->p_o_box,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['email'] != null) {
            $user->update([
                'email' => $request->email,
                'updated_at' => now(),
            ]);

            $password_reset_by_email = PasswordReset::where('email', $inputs['email'])->first();

            if ($password_reset_by_email == null) {
                $password_reset_by_phone = PasswordReset::where('phone', $current_user->phone)->first();

                if ($password_reset_by_phone != null) {
                    $password_reset_by_phone->update([
                        'email' => $inputs['email'],
                        'updated_at' => now(),
                    ]);

                } else {
                    PasswordReset::create([
                        'email' => $inputs['email'],
                    ]);
                }
            }
        }

        if ($inputs['phone'] != null) {
            $user->update([
                'phone' => $request->phone,
                'updated_at' => now(),
            ]);

            $password_reset_by_phone = PasswordReset::where('phone', $inputs['phone'])->first();

            if ($password_reset_by_phone == null) {
                $password_reset_by_email = PasswordReset::where('email', $inputs['email'])->first();

                if ($password_reset_by_email != null) {
                    $password_reset_by_email->update([
                        'phone' => $inputs['phone'],
                        'updated_at' => now(),
                    ]);

                } else {
                    PasswordReset::create([
                        'phone' => $inputs['phone'],
                    ]);
                }
            }
        }

        if ($inputs['password'] != null) {
            if ($inputs['confirm_password'] != $inputs['password'] OR $inputs['confirm_password'] == null) {
                return $this->handleError($inputs['confirm_password'], __('notifications.confirm_password_error'), 400);
            }

            if (preg_match('#^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$#', $inputs['password']) == 0) {
                return $this->handleError($inputs['password'], __('miscellaneous.password.error'), 400);
            }

            $password_reset_by_email = PasswordReset::where('email', $inputs['email'])->first();
            $password_reset_by_phone = PasswordReset::where('phone', $inputs['phone'])->first();
            $random_string = (string) random_int(1000000, 9999999);

            // If password_reset doesn't exist, create it.
            if ($password_reset_by_email == null AND $password_reset_by_phone == null) {
                if ($inputs['email'] != null AND $inputs['phone'] != null) {
                    PasswordReset::create([
                        'email' => $inputs['email'],
                        'phone' => $inputs['phone'],
                        'token' => $random_string,
                        'former_password' => $inputs['password'],
                    ]);

                } else {
                    if ($inputs['email'] != null) {
                        PasswordReset::create([
                            'email' => $inputs['email'],
                            'token' => $random_string,
                            'former_password' => $inputs['password']
                        ]);
                    }

                    if ($inputs['phone'] != null) {
                        PasswordReset::create([
                            'phone' => $inputs['phone'],
                            'token' => $random_string,
                            'former_password' => $inputs['password']
                        ]);
                    }
                }

            // Otherwise, update it.
            } else {
                if ($password_reset_by_email != null) {
                    // Update password reset
                    $password_reset_by_email->update([
                        'token' => $random_string,
                        'former_password' => $inputs['password'],
                        'updated_at' => now(),
                    ]);
                }

                if ($password_reset_by_phone != null) {
                    // Update password reset
                    $password_reset_by_phone->update([
                        'token' => $random_string,
                        'former_password' => $inputs['password'],
                        'updated_at' => now(),
                    ]);
                }
            }

            $inputs['password'] = Hash::make($inputs['password']);

            $user->update([
                'password' => $inputs['password'],
                'updated_at' => now(),
            ]);
        }

        return $this->handleResponse(new ResourcesUser($user), __('notifications.update_user_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        $users = User::orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesUser::collection($users), __('notifications.delete_user_success'));
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Search a user by his email / phone / national number.
     *
     * @param  string $data
     * @return \Illuminate\Http\Response
     */
    public function search($data)
    {
        $user = User::where('email', $data)->orWhere('phone', $data)->orWhere('national_number', $data)->first();

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        return $this->handleResponse(new ResourcesUser($user), __('notifications.find_user_success'));
    }

    /**
     * Search all users having a specific role
     *
     * @param  string $role_name
     * @return \Illuminate\Http\Response
     */
    public function findByRole($role_name)
    {
        $users = User::whereHas('roles', function ($query) use ($role_name) {
                                    $query->where('role_name', $role_name);
                                })->orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesUser::collection($users), __('notifications.find_all_users_success'));    
    }

    /**
     * Search all users having a role different than the given
     *
     * @param  string $role_name
     * @return \Illuminate\Http\Response
     */
    public function findByNotRole($role_name)
    {
        $users = User::whereDoesntHave('roles', function ($query) use ($role_name) {
                                    $query->where('role_name', $role_name);
                                })->orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesUser::collection($users), __('notifications.find_all_users_success'));    
    }

    /**
     * Search all users having specific status.
     *
     * @param  int $status_id
     * @return \Illuminate\Http\Response
     */
    public function findByStatus($status_id)
    {
        $users = User::where('status_id', $status_id)->orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesUser::collection($users), __('notifications.find_all_users_success'));
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        // Get inputs
        $inputs = [
            'username' => $request->username,
            'password' => $request->password
        ];

        if ($inputs['username'] == null OR $inputs['username'] == ' ') {
            return $this->handleError($inputs['username'], __('validation.required'), 400);
        }

        if ($inputs['password'] == null) {
            return $this->handleError($inputs['password'], __('validation.required'), 400);
        }

        if (is_numeric($inputs['username'])) {
            $user = User::where('phone', $inputs['username'])->first();

            if (!$user) {
                return $this->handleError($inputs['username'], __('auth.username'), 400);
            }

            if (!Hash::check($inputs['password'], $user->password)) {
                return $this->handleError($inputs['password'], __('auth.password'), 400);
            }

            return $this->handleResponse(new ResourcesUser($user), __('notifications.find_user_success'));

        } else {
            $user = User::where('email', $inputs['username'])->first();

            if (!$user) {
                return $this->handleError($inputs['username'], __('auth.username'), 400);
            }

            if (!Hash::check($inputs['password'], $user->password)) {
                return $this->handleError($inputs['password'], __('auth.password'), 400);
            }

            return $this->handleResponse(new ResourcesUser($user), __('notifications.find_user_success'));
        }
    }

    /**
     * Switch between user statuses.
     *
     * @param  $id
     * @param  $status_id
     * @return \Illuminate\Http\Response
     */
    public function switchStatus($id, $status_id)
    {
        $user = User::find($id);

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        /*
            HISTORY AND/OR NOTIFICATION MANAGEMENT
        */
        $status_activated = Status::where('status_name', 'Activé')->first();
        $status_blocked = Status::where('status_name', 'Bloqué')->first();
        $status_unread = Status::where('status_name', 'Non lue')->first();

        // If it's a member whose accessing is accepted, send notification
        if ($status_id == $status_activated->id) {
            Notification::create([
                'notification_url' => 'about/terms_of_use',
                'notification_content' => __('notifications.member_joined'),
                'status_id' => $status_unread->id,
                'user_id' => $user->id,
            ]);
        }

        // If it's a member whose accessing is accepted, send notification
        if ($status_id == $status_blocked->id) {
            Notification::create([
                'notification_url' => 'about/terms_of_use',
                'notification_content' => __('notifications.member_locked'),
                'status_id' => $status_unread->id,
                'user_id' => $user->id,
            ]);
        }

        // update "status_id" column
        $user->update([
            'status_id' => $status_id,
            'updated_at' => now()
        ]);

        return $this->handleResponse(new ResourcesUser($user), __('notifications.update_user_success'));
    }

    /**
     * Update user role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function updateRole(Request $request, $id)
    {
        $user = User::find($id);

        $role_user = RoleUser::where('user_id', $user->id)->first();

        $role_user->delete();

        RoleUser::create([
            'role_id' => $request->role_id,
            'user_id' => $user->id
        ]);

        return $this->handleResponse(new ResourcesUser($user), __('notifications.update_user_success'));
    }

    /**
     * Update user password in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function updatePassword(Request $request, $id)
    {
        // Get inputs
        $inputs = [
            'former_password' => $request->former_password,
            'new_password' => $request->new_password,
            'confirm_new_password' => $request->confirm_new_password
        ];
        $user = User::find($id);

        if ($inputs['former_password'] == null) {
            return $this->handleError($inputs['former_password'], __('validation.custom.former_password.empty'), 400);
        }

        if ($inputs['new_password'] == null) {
            return $this->handleError($inputs['new_password'], __('validation.custom.new_password.empty'), 400);
        }

        if ($inputs['confirm_new_password'] == null) {
            return $this->handleError($inputs['confirm_new_password'], __('notifications.confirm_new_password'), 400);
        }

        if (Hash::check($inputs['former_password'], $user->password) == false) {
            return $this->handleError($inputs['former_password'], __('auth.password'), 400);
        }

        if ($inputs['confirm_new_password'] != $inputs['new_password']) {
            return $this->handleError($inputs['confirm_new_password'], __('notifications.confirm_new_password'), 400);
        }

        if (preg_match('#^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$#', $inputs['new_password']) == 0) {
            return $this->handleError($inputs['new_password'], __('validation.custom.new_password.incorrect'), 400);
        }

        // Update password reset
        $password_reset_by_email = PasswordReset::where('email', $user->email)->first();
        $password_reset_by_phone = PasswordReset::where('phone', $user->phone)->first();

        if ($password_reset_by_email != null) {
            // Update password reset in the case user want to reset his password
            $password_reset_by_email->update([
                'code' => random_int(1000000, 9999999),
                'former_password' => $inputs['new_password'],
                'updated_at' => now(),
            ]);
        }

        if ($password_reset_by_phone != null) {
            // Update password reset in the case user want to reset his password
            $password_reset_by_phone->update([
                'code' => random_int(1000000, 9999999),
                'former_password' => $inputs['new_password'],
                'updated_at' => now(),
            ]);
        }

        // update "password" and "password_visible" column
        $user->update([
            'password' => Hash::make($inputs['new_password']),
            'updated_at' => now()
        ]);

        return $this->handleResponse(new ResourcesUser($user), __('notifications.update_password_success'));
    }

    /**
     * Get user api token in storage.
     *
     * @param  $phone
     * @return \Illuminate\Http\Response
     */
    public function getApiToken($phone)
    {
        $user = User::where('phone', $phone)->first();

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        return $this->handleResponse($user->api_token, __('notifications.find_api_token_success'));
    }

    /**
     * Update user api token in storage.
     *
     * @param  $phone
     * @return \Illuminate\Http\Response
     */
    public function updateApiToken($phone)
    {
        // find user by given ID
        $user = User::where('phone', $phone)->first();

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        // update "api_token" column
        $user->update([
            'api_token' => Str::random(100),
            'updated_at' => now()
        ]);

        return $this->handleResponse(new ResourcesUser($user), __('notifications.update_user_success'));
    }

    /**
     * Update user avatar picture in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function updateAvatarPicture(Request $request, $id)
    {
        $inputs = [
            'user_id' => $request->user_id,
            'image_64' => $request->image_64
        ];
        // $extension = explode('/', explode(':', substr($inputs['image_64'], 0, strpos($inputs['image_64'], ';')))[1])[1];
        $replace = substr($inputs['image_64'], 0, strpos($inputs['image_64'], ',') + 1);
        // Find substring from replace here eg: data:image/png;base64,
        $image = str_replace($replace, '', $inputs['image_64']);
        $image = str_replace(' ', '+', $image);

        // Clean "avatars" directory
        $file = new Filesystem;
        $file->cleanDirectory($_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/users/' . $inputs['user_id'] . '/avatar');
        // $file->cleanDirectory($_SERVER['DOCUMENT_ROOT'] . '/storage/images/users/' . $inputs['user_id'] . '/avatar');
        // Create image URL
		$image_url = 'images/users/' . $id . '/avatar/' . Str::random(50) . '.png';

		// Upload image
		Storage::url(Storage::disk('public')->put($image_url, base64_decode($image)));

		$user = User::find($id);

        $user->update([
            'avatar_url' => $image_url,
            'updated_at' => now()
        ]);

        return $this->handleResponse(new ResourcesUser($user), __('notifications.update_user_success'));
    }

    /**
     * Add user image in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function addImage(Request $request, $id)
    {
        $inputs = [
            'user_id' => $request->user_id,
            'image_name' => $request->image_name,
            'image_64_recto' => $request->image_64_recto,
            'image_64_verso' => $request->image_64_verso,
            'description' => $request->description
        ];

        $user_identity_data = Image::where('user_id', $inputs['user_id'])->first();

        if ($user_identity_data != null) {
            if ($inputs['image_64_recto'] != null AND $inputs['image_64_verso'] != null) {
                // $extension = explode('/', explode(':', substr($inputs['image_64_recto'], 0, strpos($inputs['image_64_recto'], ';')))[1])[1];
                $replace_recto = substr($inputs['image_64_recto'], 0, strpos($inputs['image_64_recto'], ',') + 1);
                $replace_verso = substr($inputs['image_64_verso'], 0, strpos($inputs['image_64_verso'], ',') + 1);
                // Find substring from replace here eg: data:image/png;base64,
                $image_recto = str_replace($replace_recto, '', $inputs['image_64_recto']);
                $image_recto = str_replace(' ', '+', $image_recto);
                $image_verso = str_replace($replace_verso, '', $inputs['image_64_verso']);
                $image_verso = str_replace(' ', '+', $image_verso);

                // Clean "identity_data" directory
                $file = new Filesystem;
                $file->cleanDirectory($_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/users/' . $inputs['user_id'] . '/identity_data');
                // $file->cleanDirectory($_SERVER['DOCUMENT_ROOT'] . '/storage/images/users/' . $inputs['user_id'] . '/identity_data');
                // Create image URL
                $image_url_recto = 'images/users/' . $inputs['user_id'] . '/identity_data/' . Str::random(50) . '.png';
                $image_url_verso = 'images/users/' . $inputs['user_id'] . '/identity_data/' . Str::random(50) . '.png';

                // Upload image
                Storage::url(Storage::disk('public')->put($image_url_recto, base64_decode($image_recto)));
                Storage::url(Storage::disk('public')->put($image_url_verso, base64_decode($image_verso)));

                $user_identity_data->delete();

                Image::create([
                    'image_name' => $inputs['image_name'],
                    'url_recto' => $image_url_recto,
                    'url_verso' => $image_url_verso,
                    'description' => $inputs['description'],
                    'user_id' => $inputs['user_id']
                ]);
            }

            if ($inputs['image_64_recto'] != null AND $inputs['image_64_verso'] == null) {
                // $extension = explode('/', explode(':', substr($inputs['image_64_recto'], 0, strpos($inputs['image_64_recto'], ';')))[1])[1];
                $replace_recto = substr($inputs['image_64_recto'], 0, strpos($inputs['image_64_recto'], ',') + 1);
                // Find substring from replace here eg: data:image/png;base64,
                $image_recto = str_replace($replace_recto, '', $inputs['image_64_recto']);
                $image_recto = str_replace(' ', '+', $image_recto);

                // Delete concerning file in "identity_data"
                if (Storage::exists($_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/users/' . $inputs['user_id'] . '/identity_data/' . $user_identity_data->url_recto)){
                    Storage::delete($_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/users/' . $inputs['user_id'] . '/identity_data/' . $user_identity_data->url_recto);
                }
                // if (Storage::exists($_SERVER['DOCUMENT_ROOT'] . '/storage/images/users/' . $inputs['user_id'] . '/identity_data/' . $user_identity_data->url_recto)){
                //     Storage::delete($_SERVER['DOCUMENT_ROOT'] . '/storage/images/users/' . $inputs['user_id'] . '/identity_data/' . $user_identity_data->url_recto);
                // }

                // Create image URL
                $image_url_recto = 'images/users/' . $inputs['user_id'] . '/identity_data/' . Str::random(50) . '.png';

                // Upload image
                Storage::url(Storage::disk('public')->put($image_url_recto, base64_decode($image_recto)));

                $user_identity_data->update([
                    'url_recto' => $image_url_recto,
                    'updated_at' => now()
                ]);
            }

            if ($inputs['image_64_recto'] == null AND $inputs['image_64_verso'] != null) {
                // $extension = explode('/', explode(':', substr($inputs['image_64_verso'], 0, strpos($inputs['image_64_verso'], ';')))[1])[1];
                $replace_verso = substr($inputs['image_64_verso'], 0, strpos($inputs['image_64_verso'], ',') + 1);
                // Find substring from replace here eg: data:image/png;base64,
                $image_verso = str_replace($replace_verso, '', $inputs['image_64_verso']);
                $image_verso = str_replace(' ', '+', $image_verso);

                // Delete concerning file in "identity_data"
                if (Storage::exists($_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/users/' . $inputs['user_id'] . '/identity_data/' . $user_identity_data->url_verso)){
                    Storage::delete($_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/users/' . $inputs['user_id'] . '/identity_data/' . $user_identity_data->url_verso);
                }
                // if (Storage::exists($_SERVER['DOCUMENT_ROOT'] . '/storage/images/users/' . $inputs['user_id'] . '/identity_data/' . $user_identity_data->url_verso)){
                //     Storage::delete($_SERVER['DOCUMENT_ROOT'] . '/storage/images/users/' . $inputs['user_id'] . '/identity_data/' . $user_identity_data->url_verso);
                // }

                // Create image URL
                $image_url_verso = 'images/users/' . $inputs['user_id'] . '/identity_data/' . Str::random(50) . '.png';

                // Upload image
                Storage::url(Storage::disk('public')->put($image_url_verso, base64_decode($image_verso)));

                $user_identity_data->update([
                    'url_verso' => $image_url_verso,
                    'updated_at' => now()
                ]);
            }

        } else {
            // $extension = explode('/', explode(':', substr($inputs['image_64_recto'], 0, strpos($inputs['image_64_recto'], ';')))[1])[1];
            $replace_recto = substr($inputs['image_64_recto'], 0, strpos($inputs['image_64_recto'], ',') + 1);
            $replace_verso = substr($inputs['image_64_verso'], 0, strpos($inputs['image_64_verso'], ',') + 1);
            // Find substring from replace here eg: data:image/png;base64,
            $image_recto = str_replace($replace_recto, '', $inputs['image_64_recto']);
            $image_recto = str_replace(' ', '+', $image_recto);
            $image_verso = str_replace($replace_verso, '', $inputs['image_64_verso']);
            $image_verso = str_replace(' ', '+', $image_verso);

            // Clean "identity_data" directory
            $file = new Filesystem;
            $file->cleanDirectory($_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/users/' . $inputs['user_id'] . '/identity_data');
            // $file->cleanDirectory($_SERVER['DOCUMENT_ROOT'] . '/storage/images/users/' . $inputs['user_id'] . '/identity_data');
            // Create image URL
            $image_url_recto = 'images/users/' . $inputs['user_id'] . '/identity_data/' . Str::random(50) . '.png';
            $image_url_verso = 'images/users/' . $inputs['user_id'] . '/identity_data/' . Str::random(50) . '.png';

            // Upload image
            Storage::url(Storage::disk('public')->put($image_url_recto, base64_decode($image_recto)));
            Storage::url(Storage::disk('public')->put($image_url_verso, base64_decode($image_verso)));

            Image::create([
                'image_name' => $inputs['image_name'],
                'url_recto' => $image_url_recto,
                'url_verso' => $image_url_verso,
                'description' => $inputs['description'],
                'user_id' => $inputs['user_id']
            ]);
        }

		$user = User::find($id);

        $user->update([
            'updated_at' => now()
        ]);

        return $this->handleResponse(new ResourcesUser($user), __('notifications.update_user_success'));
    }
}
