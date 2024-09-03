<?php

namespace App\Http\Controllers\API;

use stdClass;
use App\Models\Donation;
use App\Models\Payment;
use App\Models\Type;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\Donation as ResourcesDonation;
use App\Http\Controllers\ApiClientManager;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class DonationController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $donations = Donation::orderByDesc('created_at')->paginate(12);

        return $this->handleResponse(ResourcesDonation::collection($donations), __('notifications.find_all_donations_success'), $donations->lastPage());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Manage API Client
        $api_manager = new ApiClientManager();
        // FlexPay accessing data
        $gateway_mobile = config('services.flexpay.gateway_mobile');
        $gateway_card = config('services.flexpay.gateway_card_v2');
        // Vonage accessing data
        $basic  = new \Vonage\Client\Credentials\Basic(config('vonage.api_key'), config('vonage.api_secret'));
        $client = new \Vonage\Client($basic);
        // Transaction types
        $mobile_money_type = Type::where('type_name->fr', 'Mobile money')->first();
        $bank_card_type = Type::where('type_name->fr', 'Carte bancaire')->first();
        // Get inputs
        $inputs = [
            'amount' => $request->amount,
            'currency' => $request->currency,
            'pricing_id' => $request->pricing_id,
            'user_id' => !empty($request->user_id) ? $request->user_id : null
        ];

        // Validate required fields
        if (trim($inputs['amount']) == null) {
            return $this->handleError($inputs['amount'], __('validation.required'), 400);
        }

        if ($request->transaction_type_id == null OR !is_numeric($request->transaction_type_id)) {
            return $this->handleError(null, __('validation.required'), 400);
        }

        if (is_null($mobile_money_type)) {
            return $this->handleError(__('miscellaneous.account.my_contributions.mobile_money'), __('notifications.find_type_404'), 404);
        }

        if (is_null($bank_card_type)) {
            return $this->handleError(__('miscellaneous.account.my_contributions.bank_card'), __('notifications.find_type_404'), 404);
        }

        // If the transaction is via mobile money
        if ($request->transaction_type_id == $mobile_money_type->id) {
            // If "user_id" is empty, then it's an anonymous donation
            // if ($inputs['user_id'] != null) {
            //     $current_user = User::find($inputs['user_id']);

            //     if ($current_user != null) {
            //         $reference_code = 'REF-' . ((string) random_int(10000000, 99999999)) . '-' . $inputs['user_id'];

            //         // Create response by sending request to FlexPay
            //         $jsonRes = $api_manager::call('POST', $gateway_mobile, config('services.flexpay.api_token'), [
            //             'merchant' => 'DIKITIVI',
            //             'type' => $request->transaction_type_id,
            //             'phone' => $request->other_phone,
            //             'reference' => $reference_code,
            //             'amount' => $inputs['amount'],
            //             'currency' => $inputs['currency'],
            //             'callbackUrl' => getApiURL() . '/payment/store'
            //         ], null, null, true);

            //         if (!empty($jsonRes->error)) {
            //             return $this->handleError($jsonRes->error, $jsonRes->message, $jsonRes->status);

            //         } else {
            //             $code = $jsonRes->code;

            //             if ($code != '0') {
            //                 try {
            //                     $client->sms()->send(new \Vonage\SMS\Message\SMS($current_user->phone, 'DikiTivi', __('notifications.process_failed')));

            //                 } catch (\Throwable $th) {
            //                     return $this->handleError($th->getMessage(), __('notifications.process_failed'), 500);
            //                 }

            //                 return $this->handleError($jsonRes->code, $jsonRes->message, 400);

            //             } else {
            //                 $object = new stdClass();

            //                 $object->result_response = [
            //                     'message' => $jsonRes->message,
            //                     'order_number' => $jsonRes->orderNumber
            //                 ];

            //                 // The donation is registered only if the processing succeed
            //                 $donation = Donation::create($inputs);

            //                 $object->donation = new ResourcesDonation($donation);

            //                 // Register payment, even if FlexPay will
            //                 $payment = Payment::where('order_number', $jsonRes->orderNumber)->first();

            //                 if (is_null($payment)) {
            //                     Payment::create([
            //                         'reference' => $reference_code,
            //                         'order_number' => $jsonRes->orderNumber,
            //                         'amount' => $inputs['amount'],
            //                         'phone' => $request->other_phone,
            //                         'currency' => $inputs['currency'],
            //                         'type_id' => $request->transaction_type_id,
            //                         'status_id' => $code,
            //                         'donation_id' => $donation->id,
            //                         'user_id' => $inputs['user_id']
            //                     ]);
            //                 }

            //                 return $this->handleResponse($object, __('notifications.create_donation_success'));
            //             }
            //         }

            //     } else {
            //         return $this->handleError(__('notifications.find_user_404'));
            //     }

            // } else {
                $reference_code = 'REF-' . ((string) random_int(10000000, 99999999)) . '-ANONYMOUS';

                // Create response by sending request to FlexPay
                $jsonRes = $api_manager::call('POST', $gateway_mobile, config('services.flexpay.api_token'), [
                    'merchant' => 'DIKITIVI',
                    'type' => $request->transaction_type_id,
                    'phone' => $request->other_phone,
                    'reference' => $reference_code,
                    'amount' => $inputs['amount'],
                    'currency' => $inputs['currency'],
                    'callbackUrl' => getApiURL() . '/payment/store'
                ], null, null, true);

                if (!empty($jsonRes->error)) {
                    return $this->handleError($jsonRes->error, $jsonRes->message, $jsonRes->status);

                } else {
                    $code = $jsonRes->code;

                    if ($code != '0') {
                        return $this->handleError($jsonRes->code, $jsonRes->message, 400);

                    } else {
                        $object = new stdClass();

                        $object->result_response = [
                            'message' => $jsonRes->message,
                            'order_number' => $jsonRes->orderNumber
                        ];

                        // The donation is registered only if the processing succeed
                        $donation = Donation::create($inputs);

                        $object->donation = new ResourcesDonation($donation);

                        // Register payment, even if FlexPay will
                        $payment = Payment::where('order_number', $jsonRes->orderNumber)->first();

                        if (is_null($payment)) {
                            Payment::create([
                                'reference' => $reference_code,
                                'order_number' => $jsonRes->orderNumber,
                                'amount' => $inputs['amount'],
                                'phone' => $request->other_phone,
                                'currency' => $inputs['currency'],
                                'type_id' => $request->transaction_type_id,
                                'status_id' => $code,
                                'donation_id' => $donation->id,
                            ]);
                        }

                        return $this->handleResponse($object, __('notifications.create_donation_success'));
                    }
                }
            // }
        }

        // If the transaction is via bank card
        if ($request->transaction_type_id == $bank_card_type->id) {
            // If "user_id" is empty, then it's an anonymous donation
            // if ($inputs['user_id'] != null) {
            //     $current_user = User::find($inputs['user_id']);

            //     if ($current_user != null) {
            //         $reference_code = 'REF-' . ((string) random_int(10000000, 99999999)) . '-' . $current_user->id;

            //         $body = json_encode( array(
            //             'authorization' => "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJcL2xvZ2luIiwicm9sZXMiOlsiTUVSQ0hBTlQiXSwiZXhwIjoxNzc3MjEyNjA5LCJzdWIiOiJmNmJjMWUzYTkxYTQzNTQzMjNmODc0YWY1NGZmNzUyMyJ9.n2VVIuubjSo1f5ZFB7UfR8K-ckT1cMPTN1saiY3NhLA",
            //             'merchant' => 'DIKITIVI',
            //             'reference' => $reference_code,
            //             'amount' => $inputs['amount'],
            //             'currency' => $inputs['currency'],
            //             'description' => __('miscellaneous.bank_transaction_description'),
            //             'callback_url' => getApiURL() . '/payment/store',
            //             'approve_url' => $request->app_url . '/donated/' . $inputs['amount'] . '/' . $inputs['currency'] . '/0/' . $current_user->id,
            //             'cancel_url' => $request->app_url . '/donated/' . $inputs['amount'] . '/' . $inputs['currency'] . '/1/' . $current_user->id,
            //             'decline_url' => $request->app_url . '/donated/' . $inputs['amount'] . '/' . $inputs['currency'] . '/2/' . $current_user->id,
            //             'home_url' => $request->app_url . '/donation',
            //         ));

            //         $curl = curl_init('https://cardpayment.flexpay.cd/v1.1/pay');

            //         curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            //         curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            //         $curlResponse = curl_exec($curl);

            //         $jsonRes = json_decode($curlResponse,true);
            //         $code = $jsonRes['code'];
            //         $message = $jsonRes['message'];

            //         if (!empty($jsonRes['error'])) {
            //             return $this->handleError($jsonRes['error'], $message, $jsonRes['status']);

            //         } else {
            //             if ($jsonRes->code != '0') {
            //                 try {
            //                     $client->sms()->send(new \Vonage\SMS\Message\SMS($current_user->phone, 'DikiTivi', __('notifications.process_failed')));

            //                 } catch (\Throwable $th) {
            //                     return $this->handleError($th->getMessage(), __('notifications.process_failed'), 500);
            //                 }

            //                 return $this->handleError($code, $message, 400);

            //             } else {
            //                 $url = $jsonRes['url'];
            //                 $orderNumber = $jsonRes['orderNumber'];
            //                 $object = new stdClass();

            //                 $object->result_response = [
            //                     'message' => $message,
            //                     'order_number' => $orderNumber,
            //                     'url' => $url
            //                 ];

            //                 // The donation is registered only if the processing succeed
            //                 $donation = Donation::create($inputs);

            //                 $object->donation = new ResourcesDonation($donation);

            //                 // Register payment, even if FlexPay will
            //                 $payment = Payment::where('order_number', $orderNumber)->first();

            //                 if (is_null($payment)) {
            //                     Payment::create([
            //                         'reference' => $reference_code,
            //                         'order_number' => $jsonRes->orderNumber,
            //                         'amount' => $inputs['amount'],
            //                         'currency' => $inputs['currency'],
            //                         'type_id' => $request->transaction_type_id,
            //                         'status_id' => $jsonRes->code,
            //                         'donation_id' => $donation->id,
            //                         'user_id' => $inputs['user_id']
            //                     ]);
            //                 }

            //                 return $this->handleResponse($object, __('notifications.create_donation_success'));
            //             }
            //         }

            //     } else {
            //         return $this->handleError(__('notifications.find_user_404'));
            //     }

            // } else {
                $reference_code = 'REF-' . ((string) random_int(10000000, 99999999)) . '-ANONYMOUS';

                $body = json_encode( array(
                    'authorization' => "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJcL2xvZ2luIiwicm9sZXMiOlsiTUVSQ0hBTlQiXSwiZXhwIjoxNzc3MjEyNjA5LCJzdWIiOiJmNmJjMWUzYTkxYTQzNTQzMjNmODc0YWY1NGZmNzUyMyJ9.n2VVIuubjSo1f5ZFB7UfR8K-ckT1cMPTN1saiY3NhLA",
                    'merchant' => 'DIKITIVI',
                    'reference' => $reference_code,
                    'amount' => $inputs['amount'],
                    'currency' => $inputs['currency'],
                    'description' => __('miscellaneous.bank_transaction_description'),
                    'callback_url' => getApiURL() . '/payment/store',
                    'approve_url' => $request->app_url . '/donated/' . $inputs['amount'] . '/' . $inputs['currency'] . '/0/anonymous',
                    'cancel_url' => $request->app_url . '/donated/' . $inputs['amount'] . '/' . $inputs['currency'] . '/1/anonymous',
                    'decline_url' => $request->app_url . '/donated/' . $inputs['amount'] . '/' . $inputs['currency'] . '/2/anonymous',
                    'home_url' => $request->app_url . '/donation',
                ));

                $curl = curl_init('https://cardpayment.flexpay.cd/v1.1/pay');

                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

                $curlResponse = curl_exec($curl);

                $jsonRes = json_decode($curlResponse,true);
                $code = $jsonRes['code'];
                $message = $jsonRes['message'];

                if (!empty($jsonRes['error'])) {
                    return $this->handleError($jsonRes['error'], $message, $jsonRes['status']);

                } else {
                    if ($code != '0') {
                        return $this->handleError($code, $message, 400);

                    } else {
                        $url = $jsonRes['url'];
                        $orderNumber = $jsonRes['orderNumber'];
                        $object = new stdClass();

                        $object->result_response = [
                            'message' => $message,
                            'order_number' => $orderNumber,
                            'url' => $url
                        ];

                        // The donation is registered only if the processing succeed
                        $donation = Donation::create($inputs);

                        $object->donation = new ResourcesDonation($donation);

                        // Register payment, even if FlexPay will
                        $payment = Payment::where('order_number', $orderNumber)->first();

                        if (is_null($payment)) {
                            Payment::create([
                                'reference' => $reference_code,
                                'order_number' => $orderNumber,
                                'amount' => $inputs['amount'],
                                'currency' => $inputs['currency'],
                                'type_id' => $request->transaction_type_id,
                                'status_id' => $code,
                                'donation_id' => $donation->id,
                            ]);
                        }

                        return $this->handleResponse($object, __('notifications.create_donation_success'));
                    }
                }
            // }
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
        $donation = Donation::find($id);

        if (is_null($donation)) {
            return $this->handleError(__('notifications.find_donation_404'));
        }

        return $this->handleResponse(new ResourcesDonation($donation), __('notifications.find_donation_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Donation  $donation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Donation $donation)
    {
        // Get inputs
        $inputs = [
            'amount' => $request->amount,
            'currency' => $request->currency,
            'pricing_id' => $request->pricing_id,
            'user_id' => $request->user_id
        ];

        if ($inputs['amount'] != null) {
            $donation->update([
                'amount' => $inputs['amount'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['currency'] != null) {
            $donation->update([
                'currency' => $inputs['currency'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['pricing_id'] != null) {
            $donation->update([
                'pricing_id' => $inputs['pricing_id'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['user_id'] != null) {
            $donation->update([
                'user_id' => $inputs['user_id'],
                'updated_at' => now(),
            ]);
        }

        return $this->handleResponse(new ResourcesDonation($donation), __('notifications.update_donation_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Donation  $donation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Donation $donation)
    {
        $donation->delete();

        $donations = Donation::all();

        return $this->handleResponse(ResourcesDonation::collection($donations), __('notifications.delete_donation_success'));
    }
}
