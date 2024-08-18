<?php

namespace App\Http\Controllers\API;

use App\Http\Resources\PasswordReset as ResourcesPasswordReset;
use App\Http\Resources\Session as ResourcesSession;
use App\Http\Resources\User as ResourcesUser;
use App\Mail\OTPCode;
use App\Models\Notification;
use App\Models\PasswordReset;
use App\Models\PersonalAccessToken;
use App\Models\Session as Sessions;
use App\Models\Status;
use App\Models\User;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Nette\Utils\Random;
use Rules\Password;
use stdClass;

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
    public function userOnline()
    {
        $sessions = Sessions::whereNotNull('user_id')->get();
        $nombreOnline = Sessions::whereNotNull('user_id')->count();

        return $this->handleResponse(ResourcesSession::collection($sessions), __('notifications.find_all_sessions_success'), null, $nombreOnline);
    }

    /**
     * Store a resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $status_intermediate = Status::where('status_name->fr', 'Intermédiaire')->first();
        $status_unread = Status::where('status_name->fr', 'Non lue')->first();
        // Get inputs
        $inputs = [
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'surname' => $request->surname,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'city' => $request->city,
            'address_1' => $request->address_1,
            'address_2' => $request->address_2,
            'p_o_box' => $request->p_o_box,
            'email' => $request->email,
            'phone' => $request->phone,
            'username' => $request->username,
            'password' => empty($request->password) ? null : Hash::make($request->password),
            'belongs_to' => $request->belongs_to,
            'parental_code' => $request->parental_code,
            'api_token' => $request->api_token,
            'prefered_theme' => $request->prefered_theme,
            'country_id' => $request->country_id,
            'status_id' => is_null($status_intermediate) ? null : $status_intermediate->id,
        ];
        $users = User::all();
        $password_resets = PasswordReset::all();
        $basic = new \Vonage\Client\Credentials\Basic(config('vonage.api_key'), config('vonage.api_secret'));
        $client = new \Vonage\Client($basic);
        $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
        ]);
        // If "email" and "phone" are NULL, it means that it's a child. So, generate an email for him
        if (trim($inputs['email']) == null and trim($inputs['phone']) == null) {
            $inputs['email'] = 'child-' . Random::generate(10, '0-9a-zA-Z') . '@no_mail.com';
        }

        // if ($inputs['email'] != null) {
        //     // Check if user email already exists
        //     foreach ($users as $another_user):
        //         if ($another_user->email == $inputs['email']) {
        //             return $this->handleError($inputs['email'], __('validation.custom.email.exists'), 400);
        //         }
        //     endforeach;

        //     // If email exists in "password_reset" table, delete it
        //     if ($password_resets != null) {
        //         foreach ($password_resets as $password_reset):
        //             if ($password_reset->email == $inputs['email']) {
        //                 $password_reset->delete();
        //             }
        //         endforeach;
        //     }
        // }

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

        if ($inputs['username'] != null) {
            // Check if username already exists
            foreach ($users as $another_user):
                if ($another_user->username == $inputs['username']) {
                    return $this->handleError($inputs['username'], __('validation.custom.username.exists'), 400);
                }
            endforeach;
        }

        // If it is a child's account, generate a code for his parent if the code does not exist
        if ($inputs['belongs_to'] != null) {
            $random_string = Random::generate(7, '0-9a-zA-Z');

            $parent = User::find($inputs['belongs_to']);

            if (is_null($parent)) {
                return $this->handleError(__('notifications.find_parent_404'));
            }

            if ($parent->parental_code == null) {
                $parent->update([
                    'parental_code' => $random_string,
                    'updated_at' => now(),
                ]);
            }
        }

        if ($inputs['password'] != null) {
            if ($request->confirm_password != $request->password or $request->confirm_password == null) {
                return $this->handleError($request->confirm_password, __('notifications.confirm_password_error'), 400);
            }

            // if (preg_match('#^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$#', $inputs['password']) == 0) {
            //     return $this->handleError($inputs['password'], __('miscellaneous.password.error'), 400);
            // }

            $random_int_stringified = (string) random_int(1000000, 9999999);

            if ($inputs['email'] != null and $inputs['phone'] != null) {
                $password_reset = PasswordReset::create([
                    'email' => $inputs['email'],
                    'phone' => $inputs['phone'],
                    'token' => $random_int_stringified,
                    'former_password' => $request->password,
                ]);

                Mail::to($inputs['email'])->send(new OTPCode($password_reset->token));

                try {
                    $client->sms()->send(new \Vonage\SMS\Message\SMS($password_reset->phone, 'DikiTivi', (string) $password_reset->token));

                } catch (\Throwable $th) {
                    return $this->handleError($th->getMessage(), __('notifications.create_user_SMS_failed'), 500);
                }

            } else {
                if ($inputs['email'] != null and $inputs['phone'] == null) {
                    $password_reset = PasswordReset::create([
                        'email' => $inputs['email'],
                        'token' => $random_int_stringified,
                        'former_password' => $request->password,
                    ]);

                    Mail::to($inputs['email'])->send(new OTPCode($password_reset->token));
                }

                if ($inputs['email'] == null and $inputs['phone'] != null) {
                    $password_reset = PasswordReset::create([
                        'phone' => $inputs['phone'],
                        'token' => $random_int_stringified,
                        'former_password' => $request->password,
                    ]);

                    try {
                        $client->sms()->send(new \Vonage\SMS\Message\SMS($password_reset->phone, 'DikiTivi', (string) $password_reset->token));

                    } catch (\Throwable $th) {
                        return $this->handleError($th->getMessage(), __('notifications.create_user_SMS_failed'), 500);
                    }
                }
            }
        }

        if ($inputs['password'] == null) {
            $random_int_stringified = (string) random_int(1000000, 9999999);

            if ($inputs['email'] != null and $inputs['phone'] != null) {
                $password_reset = PasswordReset::create([
                    'email' => $inputs['email'],
                    'phone' => $inputs['phone'],
                    'token' => $random_int_stringified,
                    'former_password' => Random::generate(10, 'a-zA-Z'),
                ]);

                $inputs['password'] = Hash::make($password_reset->former_password);

                try {
                    $client->sms()->send(new \Vonage\SMS\Message\SMS($password_reset->phone, 'DikiTivi', (string) $password_reset->token));

                } catch (\Throwable $th) {
                    return $this->handleError($th->getMessage(), __('notifications.create_user_SMS_failed'), 500);
                }

            } else {
                if ($inputs['email'] != null and $inputs['phone'] == null) {
                    $password_reset = PasswordReset::create([
                        'email' => $inputs['email'],
                        'token' => $random_int_stringified,
                        'former_password' => Random::generate(10, 'a-zA-Z'),
                    ]);

                    Mail::to($inputs['email'])->send(new OTPCode($password_reset->token));

                    $inputs['password'] = Hash::make($password_reset->former_password);
                }

                if ($inputs['email'] == null and $inputs['phone'] != null) {
                    $password_reset = PasswordReset::create([
                        'phone' => $inputs['phone'],
                        'token' => $random_int_stringified,
                        'former_password' => Random::generate(10, 'a-zA-Z'),
                    ]);

                    try {
                        $client->sms()->send(new \Vonage\SMS\Message\SMS($password_reset->phone, 'DikiTivi', (string) $password_reset->token));

                    } catch (\Throwable $th) {
                        return $this->handleError($th->getMessage(), __('notifications.create_user_SMS_failed'), 500);
                    }

                    $inputs['password'] = Hash::make($password_reset->former_password);
                }
            }
        }

        $user = User::create($inputs);
        $token = $user->createToken('auth_token')->plainTextToken;

        $user->update([
            'api_token' => $token,
            'updated_at' => now(),
        ]);

        // If it is a child's account, give to the child the same password as the parent
        if ($inputs['belongs_to'] != null) {
            $parent = User::find($inputs['belongs_to']);
            $parent_password_reset = PasswordReset::where('email', $parent->email)->orWhere('phone', $parent->phone)->first();

            if (is_null($parent)) {
                return $this->handleError(__('notifications.find_parent_404'));
            }

            $user->update([
                'password' => $parent->password,
                'updated_at' => now(),
            ]);

            $password_reset->update([
                'former_password' => $parent_password_reset->former_password,
                'updated_at' => now(),
            ]);
        }

        if ($request->role_id != null) {
            $user->roles()->attach([$request->role_id]);
        }

        /*
        HISTORY AND/OR NOTIFICATION MANAGEMENT
         */
        Notification::create([
            'notification_url' => 'about/terms_of_use',
            'notification_content' => [
                'en' => 'Welcome to the DikiTivi app! Please read our terms before you start.',
                'fr' => 'Bienvenue sur l\'application DikiTivi ! Veuillez lire nos conditions avant de commencer.',
                'ln' => 'Boyei malamu na application ya DikiTivi! Tosɛngi yo otánga mibeko na biso liboso ya kobanda.',
            ],
            'icon' => 'bi bi-person-check',
            'color' => 'text-success',
            'status_id' => is_null($status_unread) ? null : $status_unread->id,
            'user_id' => $user->id,
        ]);

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

        if (!empty($user->email)) {
            $password_reset_email = PasswordReset::where('email', $user->email)->first();

            $object = new stdClass();
            $object->password_reset = new ResourcesPasswordReset($password_reset_email);
            $object->user = new ResourcesUser($user);

            return $this->handleResponse($object, __('notifications.find_user_success'));
        }

        if (!empty($user->phone)) {
            $password_reset_phone = PasswordReset::where('phone', $user->phone)->first();

            $object = new stdClass();
            $object->password_reset = new ResourcesPasswordReset($password_reset_phone);
            $object->user = new ResourcesUser($user);

            return $this->handleResponse($object, __('notifications.find_user_success'));
        }
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
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'surname' => $request->surname,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'city' => $request->city,
            'address_1' => $request->address_1,
            'address_2' => $request->address_2,
            'p_o_box' => $request->p_o_box,
            'email' => $request->email,
            'phone' => $request->phone,
            'username' => $request->username,
            'password' => $request->password,
            'confirm_password' => $request->confirm_password,
            'belongs_to' => $request->belongs_to,
            'parental_code' => $request->parental_code,
            'email_verified_at' => $request->email_verified_at,
            'phone_verified_at' => $request->phone_verified_at,
            'prefered_theme' => $request->prefered_theme,
            'country_id' => $request->country_id,
            'status_id' => $request->status,
        ];
        $users = User::all();
        $current_user = User::find($inputs['id']);

        if ($inputs['firstname'] != null) {
            $user->update([
                'firstname' => $inputs['firstname'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['lastname'] != null) {
            $user->update([
                'lastname' => $inputs['lastname'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['surname'] != null) {
            $user->update([
                'surname' => $inputs['surname'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['gender'] != null) {
            $user->update([
                'gender' => $inputs['gender'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['birth_date'] != null) {
            $user->update([
                'birth_date' => $inputs['birth_date'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['city'] != null) {
            $user->update([
                'city' => $inputs['city'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['address_1'] != null) {
            $user->update([
                'address_1' => $inputs['address_1'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['address_2'] != null) {
            $user->update([
                'address_2' => $inputs['address_2'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['p_o_box'] != null) {
            $user->update([
                'p_o_box' => $inputs['p_o_box'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['email'] != null) {
            // Check if email already exists
            foreach ($users as $another_user):
                if (!empty($current_user->email)) {
                    if ($current_user->email != $inputs['email']) {
                        if ($another_user->email == $inputs['email']) {
                            return $this->handleError($inputs['email'], __('validation.custom.email.exists'), 400);
                        }
                    }
                }
            endforeach;

            if ($current_user->email != $inputs['email']) {
                $user->update([
                    'email' => $inputs['email'],
                    'email_verified_at' => null,
                    'updated_at' => now(),
                ]);

            } else {
                $user->update([
                    'email' => $inputs['email'],
                    'updated_at' => now(),
                ]);
            }

            if (!empty($current_user->phone)) {
                $password_reset_by_phone = PasswordReset::where('phone', $current_user->phone)->first();
                $random_int_stringified = (string) random_int(1000000, 9999999);

                if ($password_reset_by_phone != null) {
                    if (!empty($password_reset_by_phone->email)) {
                        if ($password_reset_by_phone->email != $inputs['email']) {
                            $password_reset_by_phone->update([
                                'email' => $inputs['email'],
                                'former_password' => $inputs['password'] != null ? $inputs['password'] : Random::generate(10, '0-9a-zA-Z'),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    if (empty($password_reset_by_phone->email)) {
                        $password_reset_by_phone->update([
                            'email' => $inputs['email'],
                            'former_password' => $inputs['password'] != null ? $inputs['password'] : Random::generate(10, '0-9a-zA-Z'),
                            'updated_at' => now(),
                        ]);
                    }
                }

                if ($password_reset_by_phone == null) {
                    $password_reset_by_email = PasswordReset::where('email', $inputs['email'])->first();

                    if ($password_reset_by_email == null) {
                        PasswordReset::create([
                            'email' => $inputs['email'],
                            'phone' => $current_user->phone,
                            'token' => $random_int_stringified,
                            'former_password' => $inputs['password'] != null ? $inputs['password'] : Random::generate(10, 'a-zA-Z'),
                        ]);
                    }
                }

            } else {
                $random_int_stringified = (string) random_int(1000000, 9999999);

                PasswordReset::create([
                    'email' => $inputs['email'],
                    'token' => $random_int_stringified,
                    'former_password' => $inputs['password'] != null ? $inputs['password'] : Random::generate(10, 'a-zA-Z'),
                ]);
            }
        }

        if ($inputs['phone'] != null) {
            // Check if phone already exists
            foreach ($users as $another_user):
                if (!empty($current_user->phone)) {
                    if ($current_user->phone != $inputs['phone']) {
                        if ($another_user->phone == $inputs['phone']) {
                            return $this->handleError($inputs['phone'], __('validation.custom.phone.exists'), 400);
                        }
                    }
                }
            endforeach;

            if ($current_user->phone != $inputs['phone']) {
                $user->update([
                    'phone' => $inputs['phone'],
                    'phone_verified_at' => null,
                    'updated_at' => now(),
                ]);

            } else {
                $user->update([
                    'phone' => $inputs['phone'],
                    'updated_at' => now(),
                ]);
            }

            if (!empty($current_user->email)) {
                $password_reset_by_email = PasswordReset::where('email', $current_user->email)->first();
                $random_int_stringified = (string) random_int(1000000, 9999999);

                if ($password_reset_by_email != null) {
                    if (!empty($password_reset_by_email->phone)) {
                        if ($password_reset_by_email->phone != $inputs['phone']) {
                            $password_reset_by_email->update([
                                'phone' => $inputs['phone'],
                                'former_password' => $inputs['password'] != null ? $inputs['password'] : Random::generate(10, '0-9a-zA-Z'),
                                'updated_at' => now(),
                            ]);
                        }
                    }

                    if (empty($password_reset_by_email->phone)) {
                        $password_reset_by_email->update([
                            'phone' => $inputs['phone'],
                            'former_password' => $inputs['password'] != null ? $inputs['password'] : Random::generate(10, '0-9a-zA-Z'),
                            'updated_at' => now(),
                        ]);
                    }
                }

                if ($password_reset_by_email == null) {
                    $password_reset_by_phone = PasswordReset::where('phone', $inputs['phone'])->first();

                    if ($password_reset_by_email == null) {
                        PasswordReset::create([
                            'email' => $current_user->email,
                            'phone' => $inputs['phone'],
                            'token' => $random_int_stringified,
                            'former_password' => $inputs['password'] != null ? $inputs['password'] : Random::generate(10, 'a-zA-Z'),
                        ]);
                    }
                }

            } else {
                $random_int_stringified = (string) random_int(1000000, 9999999);

                PasswordReset::create([
                    'phone' => $inputs['phone'],
                    'token' => $random_int_stringified,
                    'former_password' => $inputs['password'] != null ? $inputs['password'] : Random::generate(10, 'a-zA-Z'),
                ]);
            }
        }

        if ($inputs['username'] != null) {
            // Check if username already exists
            foreach ($users as $another_user):
                if (!empty($current_user->username)) {
                    if ($current_user->username != $inputs['username']) {
                        if ($another_user->username == $inputs['username']) {
                            return $this->handleError($inputs['username'], __('validation.custom.username.exists'), 400);
                        }
                    }
                }
            endforeach;

            $user->update([
                'username' => $inputs['username'],
                'updated_at' => now(),
            ]);
        }

        // If it is a child's account, generate a code for his parent if the code does not exist
        if ($inputs['belongs_to'] != null) {
            $random_string = Random::generate(7, '0-9a-zA-Z');

            $parent = User::find($inputs['belongs_to']);

            if (is_null($parent)) {
                return $this->handleError(__('notifications.find_parent_404'));
            }

            if ($parent->parental_code == null) {
                $parent->update([
                    'parental_code' => $random_string,
                    'updated_at' => now(),
                ]);
            }

            $user->update([
                'belongs_to' => $inputs['belongs_to'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['parental_code'] != null) {
            $user->update([
                'parental_code' => $inputs['parental_code'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['email_verified_at'] != null) {
            $user->update([
                'email_verified_at' => $inputs['email_verified_at'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['phone_verified_at'] != null) {
            $user->update([
                'phone_verified_at' => $inputs['phone_verified_at'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['password'] != null) {
            if ($inputs['confirm_password'] != $inputs['password'] or $inputs['confirm_password'] == null) {
                return $this->handleError($inputs['confirm_password'], __('notifications.confirm_password_error'), 400);
            }

            // if (preg_match('#^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$#', $inputs['password']) == 0) {
            //     return $this->handleError($inputs['password'], __('miscellaneous.password.error'), 400);
            // }

            if (!empty($current_user->email)) {
                $password_reset = PasswordReset::where('email', $current_user->email)->first();
                $random_int_stringified = (string) random_int(1000000, 9999999);

                // If password_reset exists, update it
                if ($password_reset != null) {
                    $password_reset->update([
                        'token' => $random_int_stringified,
                        'former_password' => $inputs['password'],
                        'updated_at' => now(),
                    ]);
                }

            } else {
                if (!empty($current_user->phone)) {
                    $password_reset = PasswordReset::where('phone', $current_user->phone)->first();
                    $random_int_stringified = (string) random_int(1000000, 9999999);

                    // If password_reset exists, update it
                    if ($password_reset != null) {
                        $password_reset->update([
                            'token' => $random_int_stringified,
                            'former_password' => $inputs['password'],
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            $user->update([
                'password' => Hash::make($inputs['password']),
                'updated_at' => now(),
            ]);

            // If the user is a parent, change its children's password according to its own
            if (!empty($user->parental_code)) {
                $children = User::where('belongs_to', $user->id)->get();

                foreach ($children as $child):
                    $child->update([
                        'password' => $user->password,
                        'updated_at' => now(),
                    ]);

                    if (!empty($child->email)) {
                        $child_password_reset = PasswordReset::where('email', $child->email)->first();

                        $child_password_reset->update([
                            'former_password' => $inputs['password'],
                            'updated_at' => now(),
                        ]);

                    } else {
                        if (!empty($child->phone)) {
                            $child_password_reset = PasswordReset::where('phone', $child->phone)->first();

                            $child_password_reset->update([
                                'former_password' => $inputs['password'],
                                'updated_at' => now(),
                            ]);
                        }
                    }
                endforeach;
            }
        }

        if ($inputs['prefered_theme'] != null) {
            $user->update([
                'prefered_theme' => $inputs['prefered_theme'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['country_id'] != null) {
            $user->update([
                'country_id' => $inputs['country_id'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['status_id'] != null) {
            $user->update([
                'status_id' => $inputs['status_id'],
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
        $password_reset_email = PasswordReset::whereNotNull('email')->where('email', $user->email)->first();
        $password_reset_phone = PasswordReset::whereNotNull('phone')->where('phone', $user->phone)->first();
        $personal_access_tokens = PersonalAccessToken::where('tokenable_id', $user->id)->get();
        $notifications = Notification::where('user_id', $user->id)->get();
        $children = User::where('belongs_to', $user->id)->get();
        $directory = $_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/users/' . $user->id;

        if (!is_null($personal_access_tokens)) {
            foreach ($personal_access_tokens as $personal_access_token):
                $personal_access_token->delete();
            endforeach;
        }

        if (!is_null($notifications)) {
            foreach ($notifications as $notification):
                $notification->delete();
            endforeach;
        }

        if (!is_null($children)) {
            foreach ($children as $child):
                $child->update([
                    'belongs_to' => null,
                    'updated_at' => now(),
                ]);
            endforeach;
        }

        if (Storage::exists($directory)) {
            Storage::deleteDirectory($directory);
        }

        if ($password_reset_email != null and $password_reset_phone != null) {
            $password_reset_email->delete();

        } else {
            if ($password_reset_email == null and $password_reset_phone != null) {
                $password_reset_phone->delete();
            }

            if ($password_reset_email != null and $password_reset_phone == null) {
                $password_reset_email->delete();
            }
        }

        $user->delete();

        $users = User::orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesUser::collection($users), __('notifications.delete_user_success'));
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Find by "username"
     *
     * @param  string $username
     * @return \Illuminate\Http\Response
     */
    public function profile($username)
    {
        $user = User::where('username', $username)->first();

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        return $this->handleResponse(new ResourcesUser($user), __('notifications.find_user_success'));
    }

    /**
     * Search all users having a specific role
     *
     * @param  string $locale
     * @param  string $role_name
     * @return \Illuminate\Http\Response
     */
    public function findByRole($locale, $role_name)
    {
        $users = User::whereHas('roles', function ($query) use ($locale, $role_name) {
            $query->where('role_name', $role_name);
        })->orderByDesc('users.created_at')->get();

        return $this->handleResponse(ResourcesUser::collection($users), __('notifications.find_all_users_success'));
    }

    /**
     * Search all users having a role different than the given
     *
     * @param  string $locale
     * @param  string $role_name
     * @return \Illuminate\Http\Response
     */
    public function findByNotRole($locale, $role_name)
    {
        $users = User::whereDoesntHave('roles', function ($query) use ($locale, $role_name) {
            $query->where('role_name->' . $locale, $role_name);
        })->orderByDesc('users.created_at')->get();

        return $this->handleResponse(ResourcesUser::collection($users), __('notifications.find_all_users_success'));
    }

    /**
     * Search all users having specific status.
     *
     * @param  string $status_id
     * @return \Illuminate\Http\Response
     */
    public function findByStatus($status_id)
    {
        $users = User::where('status_id', $status_id)->orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesUser::collection($users), __('notifications.find_all_users_success'));
    }

    /**
     * Search all users having a ID card.
     *
     * @param  string $status_id
     * @return \Illuminate\Http\Response
     */
    public function findByIdCard()
    {
        $users = User::whereNotNull('id_card_recto')->orderByDesc('created_at')->get();

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
            'password' => $request->password,
        ];

        if ($inputs['username'] == null or $inputs['username'] == ' ') {
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

            if ($user->phone_verified_at == null) {
                $password_reset = PasswordReset::where('phone', $user->phone)->first();
                $object = new stdClass();

                $object->password_reset = new ResourcesPasswordReset($password_reset);
                $object->user = new ResourcesUser($user);

                return $this->handleError($object, __('notifications.unverified_token_phone'), 400);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $user->update([
                'api_token' => $token,
                'updated_at' => now(),
            ]);

            return $this->handleResponse(new ResourcesUser($user), __('notifications.find_user_success'));

        } else {
            $user = User::where('email', $inputs['username'])->orWhere('username', $inputs['username'])->first();

            if (!$user) {
                return $this->handleError($inputs['username'], __('auth.username'), 400);
            }

            if (!Hash::check($inputs['password'], $user->password)) {
                return $this->handleError($inputs['password'], __('auth.password'), 400);
            }

            if (!empty($user->email)) {
                if ($inputs['username'] == $user->email) {
                    if ($user->email_verified_at == null) {
                        $password_reset = PasswordReset::where('email', $user->email)->first();
                        $object = new stdClass();

                        $object->password_reset = new ResourcesPasswordReset($password_reset);
                        $object->user = new ResourcesUser($user);

                        return $this->handleError($object, __('notifications.unverified_token_email'), 400);
                    }
                }
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            $user->update([
                'api_token' => $token,
                'updated_at' => now(),
            ]);

            return $this->handleResponse(new ResourcesUser($user), __('notifications.find_user_success'));
        }
    }

    /**
     * Search a user by a parental code.
     *
     * @param  int $user_id
     * @param  string $parental_code
     * @return \Illuminate\Http\Response
     */
    public function findByParentalCode($user_id, $parental_code)
    {
        $user = User::find($user_id);

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        $parent = User::where('parental_code', $parental_code)->whereNull('belongs_to')->first();

        if (is_null($parent)) {
            return $this->handleError(__('notifications.find_parent_404'));
        }

        if ($user->id != $parent->id) {
            return $this->handleError(__('notifications.children_not_allowed'));
        }

        $users = User::where('belongs_to', $parent->id)->get();

        if (count($users) == 0) {
            return $this->handleResponse(ResourcesUser::collection($users), __('miscellaneous.empty_list'));
        }

        return $this->handleResponse(ResourcesUser::collection($users), __('notifications.find_all_users_success'));
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
        $status_activated = Status::where('status_name->fr', 'Activé')->first();
        $status_intermediate = Status::where('status_name->fr', 'Intermédiaire')->first();
        $status_blocked = Status::where('status_name->fr', 'Bloqué')->first();
        $status_unread = Status::where('status_name->fr', 'Non lue')->first();

        // If it's a member whose accessing is accepted, send notification
        if ($status_id == $status_activated->id or $status_id == $status_intermediate->id) {
            Notification::create([
                'notification_url' => 'about/terms_of_use',
                'notification_content' => [
                    'en' => 'Your account has been activated. Please read our terms before you start.',
                    'fr' => 'Votre compte a été activé. Veuillez lire nos conditions avant de commencer.',
                    'ln' => 'Compte na yo esili ko activer. Tosɛngi yo otánga mibeko na biso liboso ya kobanda.',
                ],
                'icon' => 'bi bi-unlock-fill',
                'color' => 'text-info',
                'status_id' => $status_unread->id,
                'user_id' => $user->id,
            ]);

            if ($user->id_card_recto == null and $user->id_card_verso == null) {
                // update "status_id" column
                $user->update([
                    'status_id' => $status_intermediate->id,
                    'updated_at' => now(),
                ]);

            } else {
                // update "status_id" column
                $user->update([
                    'status_id' => $status_activated->id,
                    'updated_at' => now(),
                ]);
            }
        }

        // If it's a member whose accessing is blocked, send notification
        if ($status_id == $status_blocked->id) {
            Notification::create([
                'notification_url' => 'about/terms_of_use',
                'notification_content' => [
                    'en' => 'Your account has been blocked. If you have any questions, contact us via the telephone number displayed on our website.',
                    'fr' => 'Votre compte a été bloqué. Si vous avez des questions, contactez-nous via le n° de téléphone affiché sur notre site web.',
                    'ln' => 'Compte na yo ekangami. Soki ozali na mituna, benga biso na nzela ya nimero ya telefone oyo emonisami na site Internet na biso.',
                ],
                'icon' => 'bi bi-lock-fill',
                'color' => 'text-danger',
                'status_id' => $status_unread->id,
                'user_id' => $user->id,
            ]);

            // update "status_id" column
            $user->update([
                'status_id' => $status_blocked->id,
                'updated_at' => now(),
            ]);
        }

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

        $user->roles()->syncWithoutDetaching([$request->role_id]);

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
            'confirm_new_password' => $request->confirm_new_password,
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

        // if (preg_match('#^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$#', $inputs['new_password']) == 0) {
        //     return $this->handleError($inputs['new_password'], __('validation.custom.new_password.incorrect'), 400);
        // }

        // Update password reset
        if (!empty($user->email) and !empty($user->phone)) {
            $password_reset = PasswordReset::where([['email', $user->email], ['phone', $user->phone]])->first();
            $random_int_stringified = (string) random_int(1000000, 9999999);

            // If password_reset doesn't exist, create it.
            if ($password_reset == null) {
                PasswordReset::create([
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'token' => $random_int_stringified,
                    'former_password' => $inputs['new_password'],
                ]);
            }

            // If password_reset exists, update it
            if ($password_reset != null) {
                $password_reset->update([
                    'token' => $random_int_stringified,
                    'former_password' => $inputs['new_password'],
                    'updated_at' => now(),
                ]);
            }

        } else {
            if (!empty($user->email)) {
                $password_reset = PasswordReset::where('email', $user->email)->first();
                $random_int_stringified = (string) random_int(1000000, 9999999);

                // If password_reset doesn't exist, create it.
                if ($password_reset == null) {
                    PasswordReset::create([
                        'email' => $user->email,
                        'token' => $random_int_stringified,
                        'former_password' => $inputs['new_password'],
                    ]);
                }

                // If password_reset exists, update it
                if ($password_reset != null) {
                    $password_reset->update([
                        'token' => $random_int_stringified,
                        'former_password' => $inputs['new_password'],
                        'updated_at' => now(),
                    ]);
                }
            }

            if (!empty($user->phone)) {
                $password_reset = PasswordReset::where('phone', $user->phone)->first();
                $random_int_stringified = (string) random_int(1000000, 9999999);

                // If password_reset doesn't exist, create it.
                if ($password_reset == null) {
                    PasswordReset::create([
                        'phone' => $user->phone,
                        'token' => $random_int_stringified,
                        'former_password' => $inputs['new_password'],
                    ]);
                }

                // If password_reset exists, update it
                if ($password_reset != null) {
                    $password_reset->update([
                        'token' => $random_int_stringified,
                        'former_password' => $inputs['new_password'],
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // update "password" and "password_visible" column
        $user->update([
            'password' => Hash::make($inputs['new_password']),
            'updated_at' => now(),
        ]);

        // If the user is a parent, change its children's password according to its own
        if (!empty($user->parental_code)) {
            $children = User::where('belongs_to', $user->id)->get();

            foreach ($children as $child):
                $child->update([
                    'password' => $user->password,
                    'updated_at' => now(),
                ]);

                if (!empty($child->email)) {
                    $child_password_reset = PasswordReset::where('email', $child->email)->first();

                    $child_password_reset->update([
                        'former_password' => $inputs['password'],
                        'updated_at' => now(),
                    ]);

                } else {
                    if (!empty($child->phone)) {
                        $child_password_reset = PasswordReset::where('phone', $child->phone)->first();

                        $child_password_reset->update([
                            'former_password' => $inputs['password'],
                            'updated_at' => now(),
                        ]);
                    }
                }
            endforeach;
        }

        return $this->handleResponse(new ResourcesUser($user), __('notifications.update_password_success'));
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
            'image_64' => $request->image_64,
        ];
        // $extension = explode('/', explode(':', substr($inputs['image_64'], 0, strpos($inputs['image_64'], ';')))[1])[1];
        $replace = substr($inputs['image_64'], 0, strpos($inputs['image_64'], ',') + 1);
        // Find substring from replace here eg: data:image/png;base64,
        $image = str_replace($replace, '', $inputs['image_64']);
        $image = str_replace(' ', '+', $image);

        // Clean "avatars" directory
        $file = new Filesystem;
        $file->cleanDirectory($_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/users/' . $inputs['user_id'] . '/avatar');
        // Create image URL
        $image_url = 'images/users/' . $id . '/avatar/' . Str::random(50) . '.png';

        // Upload image
        Storage::url(Storage::disk('public')->put($image_url, base64_decode($image)));

        $user = User::find($id);

        $user->update([
            'avatar_url' => $image_url,
            'updated_at' => now(),
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
        ];

        if ($inputs['image_64_recto'] != null and $inputs['image_64_verso'] != null) {
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
            // Create image URL
            $image_url_recto = 'images/users/' . $id . '/identity_data/' . Str::random(50) . '.png';
            $image_url_verso = 'images/users/' . $id . '/identity_data/' . Str::random(50) . '.png';

            // Upload image
            Storage::url(Storage::disk('public')->put($image_url_recto, base64_decode($image_recto)));
            Storage::url(Storage::disk('public')->put($image_url_verso, base64_decode($image_verso)));

            $user = User::find($id);

            $user->update([
                'id_card_type' => $inputs['image_name'],
                'id_card_recto' => $image_url_recto,
                'id_card_verso' => $image_url_verso,
                'updated_at' => now(),
            ]);

            return $this->handleResponse(new ResourcesUser($user), __('notifications.update_user_success'));

        } else {
            if ($inputs['image_64_recto'] != null and $inputs['image_64_verso'] == null) {
                // $extension = explode('/', explode(':', substr($inputs['image_64_recto'], 0, strpos($inputs['image_64_recto'], ';')))[1])[1];
                $replace_recto = substr($inputs['image_64_recto'], 0, strpos($inputs['image_64_recto'], ',') + 1);
                // Find substring from replace here eg: data:image/png;base64,
                $image_recto = str_replace($replace_recto, '', $inputs['image_64_recto']);
                $image_recto = str_replace(' ', '+', $image_recto);

                // Clean "identity_data" directory
                $file = new Filesystem;
                $file->cleanDirectory($_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/users/' . $inputs['user_id'] . '/identity_data');
                // Create image URL
                $image_url_recto = 'images/users/' . $id . '/identity_data/' . Str::random(50) . '.png';

                // Upload image
                Storage::url(Storage::disk('public')->put($image_url_recto, base64_decode($image_recto)));

                $user = User::find($id);

                $user->update([
                    'id_card_type' => $inputs['image_name'],
                    'id_card_recto' => $image_url_recto,
                    'updated_at' => now(),
                ]);

                return $this->handleResponse(new ResourcesUser($user), __('notifications.update_user_success'));
            }

            if ($inputs['image_64_recto'] == null and $inputs['image_64_verso'] != null) {
                // $extension = explode('/', explode(':', substr($inputs['image_64_verso'], 0, strpos($inputs['image_64_verso'], ';')))[1])[1];
                $replace_verso = substr($inputs['image_64_verso'], 0, strpos($inputs['image_64_verso'], ',') + 1);
                // Find substring from replace here eg: data:image/png;base64,
                $image_verso = str_replace($replace_verso, '', $inputs['image_64_verso']);
                $image_verso = str_replace(' ', '+', $image_verso);

                // Clean "identity_data" directory
                $file = new Filesystem;
                $file->cleanDirectory($_SERVER['DOCUMENT_ROOT'] . '/public/storage/images/users/' . $inputs['user_id'] . '/identity_data');
                // Create image URL
                $image_url_verso = 'images/users/' . $id . '/identity_data/' . Str::random(50) . '.png';

                // Upload image
                Storage::url(Storage::disk('public')->put($image_url_verso, base64_decode($image_verso)));

                $user = User::find($id);

                $user->update([
                    'id_card_type' => $inputs['image_name'],
                    'id_card_verso' => $image_url_verso,
                    'updated_at' => now(),
                ]);

                return $this->handleResponse(new ResourcesUser($user), __('notifications.update_user_success'));
            }
        }
    }
}
