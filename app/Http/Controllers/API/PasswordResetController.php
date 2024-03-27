<?php

namespace App\Http\Controllers\API;

use stdClass;
use App\Mail\OTPCode;
use App\Models\PasswordReset;
use App\Models\User;
use Nette\Utils\Random;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\PasswordReset as ResourcesPasswordReset;
use App\Http\Resources\User as ResourcesUser;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class PasswordResetController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $password_resets = PasswordReset::orderByDesc('updated_at')->get();

        return $this->handleResponse(ResourcesPasswordReset::collection($password_resets), __('notifications.find_all_password_resets_success'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $random_string = (string) random_int(1000000, 9999999);
        // Get inputs
        $inputs = [
            'email' => $request->email,
            'phone' => $request->phone,
            'token' => $random_string,
            'former_password' => $request->former_password
        ];

        // Validate required fields
        if ($inputs['email'] == null AND $inputs['phone'] == null) {
            return $this->handleError(__('validation.email_or_phone.required'), 400);
        }

        if ($inputs['email'] == ' ' AND $inputs['phone'] == ' ') {
            return $this->handleError(__('validation.email_or_phone.required'), 400);
        }

        if ($inputs['email'] == null AND $inputs['phone'] == ' ') {
            return $this->handleError(__('validation.email_or_phone.required'), 400);
        }

        if ($inputs['email'] == ' ' AND $inputs['phone'] == null) {
            return $this->handleError(__('validation.email_or_phone.required'), 400);
        }

        if ($inputs['email'] != null) {
            $existing_password_reset = PasswordReset::where('email', $inputs['email'])->first();

            if ($existing_password_reset != null) {
                $existing_password_reset->delete();
            }

            $password_reset = PasswordReset::create($inputs);

            return $this->handleResponse(new ResourcesPasswordReset($password_reset), __('notifications.create_password_reset_success'));
        }

        if ($inputs['phone'] != null) {
            $existing_password_reset = PasswordReset::where('phone', $inputs['phone'])->first();

            if ($existing_password_reset != null) {
                $existing_password_reset->delete();
            }

            $password_reset = PasswordReset::create($inputs);

            return $this->handleResponse(new ResourcesPasswordReset($password_reset), __('notifications.create_password_reset_success'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $password_reset = PasswordReset::find($id);

        if (is_null($password_reset)) {
            return $this->handleError(__('notifications.find_password_reset_404'));
        }

        return $this->handleResponse(new ResourcesPasswordReset($password_reset), __('notifications.find_password_reset_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PasswordReset  $password_reset
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PasswordReset $password_reset)
    {
        $random_string = (string) random_int(1000000, 9999999);
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'email' => $request->email,
            'phone' => $request->phone,
            'token' => $random_string,
            'former_password' => $request->former_password,
            'updated_at' => now()
        ];

        if ($inputs['email'] == null AND $inputs['phone'] == null) {
            return $this->handleError(__('validation.email_or_phone.required'), 400);
        }

        if ($inputs['email'] == ' ' AND $inputs['phone'] == ' ') {
            return $this->handleError(__('validation.email_or_phone.required'), 400);
        }

        if ($inputs['email'] == null AND $inputs['phone'] == ' ') {
            return $this->handleError(__('validation.email_or_phone.required'), 400);
        }

        if ($inputs['email'] == ' ' AND $inputs['phone'] == null) {
            return $this->handleError(__('validation.email_or_phone.required'), 400);
        }

        $password_reset->update($inputs);

        return $this->handleResponse(new ResourcesPasswordReset($password_reset), __('notifications.update_password_reset_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PasswordReset  $password_reset
     * @return \Illuminate\Http\Response
     */
    public function destroy(PasswordReset $password_reset)
    {
        $password_reset->delete();

        $password_resets = PasswordReset::all();

        return $this->handleResponse(ResourcesPasswordReset::collection($password_resets), __('notifications.delete_password_reset_success'));
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Search a password reset by e-mail or phone number
     *
     * @param  string $data
     * @return \Illuminate\Http\Response
     */
    public function searchByEmailOrPhone($data)
    {
        $random_string = (string) random_int(1000000, 9999999);

        if (is_numeric($data)) {
            $password_reset = PasswordReset::where('phone', $data)->first();
            $user = User::where('phone', $data)->first();

            if (is_null($user)) {
                return $this->handleError(__('notifications.find_user_404'));
            }

            if (is_null($password_reset)) {
                return $this->handleError(__('notifications.find_password_reset_404'));
            }

            $password_reset->update([
                'former_password' => Random::generate(7),
                'token' => $random_string,
                'updated_at' => now()
            ]);

            $user->update([
                'password' => Hash::make($password_reset->former_password),
                'updated_at' => now()
            ]);

            $object = new stdClass();
            $object->user = new ResourcesUser($user);
            $object->password_reset = new ResourcesPasswordReset($password_reset);

            return $this->handleResponse($object, __('notifications.find_password_reset_success'));

        } else {
            $password_reset = PasswordReset::where('email', $data)->first();
            $user = User::where('email', $data)->first();

            if (is_null($user)) {
                return $this->handleError(__('notifications.find_user_404'));
            }

            if (is_null($password_reset)) {
                return $this->handleError(__('notifications.find_password_reset_404'));
            }

            $password_reset->update([
                'former_password' => Random::generate(7),
                'token' => $random_string,
                'updated_at' => now()
            ]);

            $user->update([
                'password' => Hash::make($password_reset->former_password),
                'updated_at' => now()
            ]);

            $object = new stdClass();
            $object->user = new ResourcesUser($user);
            $object->password_reset = new ResourcesPasswordReset($password_reset);

            return $this->handleResponse($object, __('notifications.find_password_reset_success'));
        }
    }

    /**
     * Search a password reset by e-mail
     *
     * @param  string $data
     * @return \Illuminate\Http\Response
     */
    public function searchByEmail($data)
    {
        $password_reset = PasswordReset::where('email', $data)->first();
        $user = User::where('email', $data)->first();

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        if (is_null($password_reset)) {
            return $this->handleError(__('notifications.find_password_reset_404'));
        }

        if ($password_reset->email != null) {
            $random_string = (string) random_int(1000000, 9999999);

            $password_reset->update([
                'former_password' => Random::generate(7),
                'token' => $random_string,
                'updated_at' => now()
            ]);

            $user->update([
                'password' => Hash::make($password_reset->former_password),
                'updated_at' => now()
            ]);

            Mail::to($password_reset->email)->send(new OTPCode($password_reset->token));
        }

        $object = new stdClass();
        $object->user = new ResourcesUser($user);
        $object->password_reset = new ResourcesPasswordReset($password_reset);

        return $this->handleResponse($object, __('notifications.find_password_reset_success'));
    }

    /**
     * Search a password reset by phone
     *
     * @param  string $data
     * @return \Illuminate\Http\Response
     */
    public function searchByPhone($data)
    {
        $password_reset = PasswordReset::where('phone', $data)->first();
        $user = User::where('phone', $data)->first();
        $basic  = new \Vonage\Client\Credentials\Basic(config('vonage.api_key'), config('vonage.api_secret'));
        $client = new \Vonage\Client($basic);

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        if (is_null($password_reset)) {
            return $this->handleError(__('notifications.find_password_reset_404'));
        }

        if ($password_reset->phone != null) {
            $random_string = (string) random_int(1000000, 9999999);

            $password_reset->update([
                'token' => $random_string,
                'updated_at' => now()
            ]);

            $user->update([
                'password' => Hash::make($password_reset->former_password),
                'updated_at' => now()
            ]);

            try {
                $client->sms()->send(new \Vonage\SMS\Message\SMS($password_reset->phone, 'DikiTivi', (string) $password_reset->token));

            } catch (\Throwable $th) {
                $response_error = json_decode($th->getMessage(), false);

                return $this->handleError($response_error, __('notifications.create_user_SMS_failed'), 500);
            }
        }

        $object = new stdClass();
        $object->user = new ResourcesUser($user);
        $object->password_reset = new ResourcesPasswordReset($password_reset);

        return $this->handleResponse($object, __('notifications.find_password_reset_success'));
    }

    /**
     * Check the password reset token validity.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkToken(Request $request)
    {
        // Get inputs
        $inputs = [
            'email' => $request->email,
            'phone' => $request->phone,
            'token' => $request->token
        ];

        if (trim($inputs['email']) == null AND trim($inputs['phone']) == null) {
            return $this->handleError(__('validation.custom.email_or_phone.required'));
        }

        if (trim($inputs['token']) == null) {
            return $this->handleError($inputs['token'], __('validation.required'), 400);
        }

        if ($inputs['email'] != null AND $inputs['phone'] != null) {
            $user_by_email = User::where('email', 'email')->first();
            $user_by_phone = User::where('phone', 'phone')->first();
            $password_reset_by_email = PasswordReset::where('email', 'email')->first();
            $password_reset_by_phone = PasswordReset::where('phone', 'phone')->first();

            if ($user_by_email != null) {
                if (is_null($password_reset_by_email)) {
                    return $this->handleError(__('notifications.find_password_reset_404'));
                }

                if ($password_reset_by_email->token != $inputs['token']) {
                    return $this->handleError($inputs['token'], __('notifications.bad_token'), 400);
                }

                $user_by_email->update([
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                ]);    
            }

            if ($user_by_phone != null) {
                if (is_null($password_reset_by_phone)) {
                    return $this->handleError(__('notifications.find_password_reset_404'));
                }

                if ($password_reset_by_phone->token != $inputs['token']) {
                    return $this->handleError($inputs['token'], __('notifications.bad_token'), 400);
                }

                $user_by_phone->update([
                    'phone_verified_at' => now(),
                    'updated_at' => now(),
                ]);    
            }

            $object = new stdClass();
            $object->user = new ResourcesUser(($user_by_phone != null ? $user_by_phone : $user_by_email));
            $object->password_reset = new ResourcesPasswordReset(($password_reset_by_phone != null ? $password_reset_by_phone : $password_reset_by_email));

            return $this->handleResponse($object, __('notifications.update_user_success'));

        } else {
			if ($inputs['email'] != null) {
				$user = User::where('email', $inputs['email'])->first();
				$password_reset = PasswordReset::where('email', $inputs['email'])->first();

				if (is_null($user)) {
					return $this->handleError(__('notifications.find_user_404'));
				}

				if (is_null($password_reset)) {
					return $this->handleError(__('notifications.find_password_reset_404'));
				}
		
				if ($password_reset->token != $inputs['token']) {
					return $this->handleError($inputs['token'], __('notifications.bad_token'), 400);
				}

				$user->update([
					'email_verified_at' => now(),
					'updated_at' => now(),
				]);

				$object = new stdClass();
				$object->user = new ResourcesUser($user);
				$object->password_reset = new ResourcesPasswordReset($password_reset);

				return $this->handleResponse($object, __('notifications.update_user_success'));
			}

			if ($inputs['phone'] != null) {
				$user = User::where('phone', $inputs['phone'])->first();
				$password_reset = PasswordReset::where('phone', $inputs['phone'])->first();

				if (is_null($user)) {
					return $this->handleError(__('notifications.find_user_404'));
				}

				if (is_null($password_reset)) {
					return $this->handleError(__('notifications.find_password_reset_404'));
				}
		
				if ($password_reset->token != $inputs['token']) {
					return $this->handleError($inputs['token'], __('notifications.bad_token'), 400);
				}

				$user->update([
					'phone_verified_at' => now(),
					'updated_at' => now(),
				]);

				$object = new stdClass();
				$object->user = new ResourcesUser($user);
				$object->password_reset = new ResourcesPasswordReset($password_reset);

				return $this->handleResponse($object, __('notifications.update_user_success'));
			}
		}
    }
}
