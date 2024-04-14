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
            'pricing_id' => $request->pricing_id,
            'user_id' => $request->user_id
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
            if ($inputs['user_id'] != null AND is_numeric($inputs['user_id'])) {
                $current_user = User::find($inputs['user_id']);

                if ($current_user != null) {
                    $reference_code = 'REF-' . ((string) random_int(10000000, 99999999)) . '-' . $inputs['user_id'];

                    // Create response by sending request to FlexPay
                    $jsonRes = $api_manager::call('POST', $gateway_mobile, config('services.flexpay.api_token'), [
                        'merchant' => 'ATAMBUTU',
                        'type' => $request->transaction_type_id,
                        'phone' => $request->other_phone,
                        'reference' => $reference_code,
                        'amount' => $inputs['amount'],
                        'currency' => $request->currency,
                        'callbackUrl' => getApiURL() . '/payment/store'
                    ]);
                    $code = $jsonRes->code;

                    if ($code != '0') {
                        try {
                            $client->sms()->send(new \Vonage\SMS\Message\SMS($current_user->phone, 'DikiTivi', __('notifications.create_user_SMS_failed')));

                        } catch (\Throwable $th) {
                            return $this->handleError($th->getMessage(), __('notifications.create_user_SMS_failed'), 500);
                        }

                        return $this->handleError(__('notifications.process_failed'));

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
                                'reference' => $reference,
                                'order_number' => $jsonRes->orderNumber,
                                'amount' => $inputs['amount'],
                                'phone' => $request->other_phone,
                                'currency' => $request->currency,
                                'type_id' => $request->transaction_type_id,
                                'donation_id' => $donation->id,
                                'user_id' => $inputs['user_id']
                            ]);
                        }

                        return $this->handleResponse($object, __('notifications.create_donation_success'));
                    }

                } else {
                    return $this->handleError(__('notifications.find_user_404'));
                }

            } else {
                $reference_code = 'REF-' . ((string) random_int(10000000, 99999999)) . '-ANONYMOUS';

                // Create response by sending request to FlexPay
                $jsonRes = $api_manager::call('POST', $gateway_mobile, config('services.flexpay.api_token'), [
                    'merchant' => 'ATAMBUTU',
                    'type' => $request->transaction_type_id,
                    'phone' => $request->other_phone,
                    'reference' => $reference_code,
                    'amount' => $inputs['amount'],
                    'currency' => $request->currency,
                    'callbackUrl' => getApiURL() . '/payment/store'
                ]);
                $code = $jsonRes->code;

                if ($code != '0') {
                    return $this->handleError(__('notifications.process_failed'));

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
                            'reference' => $reference,
                            'order_number' => $jsonRes->orderNumber,
                            'amount' => $inputs['amount'],
                            'phone' => $request->other_phone,
                            'currency' => $request->currency,
                            'type_id' => $request->transaction_type_id,
                            'donation_id' => $donation->id,
                        ]);
                    }

                    return $this->handleResponse($object, __('notifications.create_donation_success'));
                }
            }
        }

        // If the transaction is via bank card
        if ($request->transaction_type_id == $bank_card_type->id) {
            // If "user_id" is empty, then it's an anonymous donation
            if ($inputs['user_id'] != null AND is_numeric($inputs['user_id'])) {
                $current_user = User::find($inputs['user_id']);

                if ($current_user != null) {
                    $reference_code = 'REF-' . ((string) random_int(10000000, 99999999)) . '-' . $current_user->id;

                    // Create response by sending request to FlexPay
                    $jsonRes = $api_manager::call('POST', $gateway_card, config('services.flexpay.api_token'), [
                        'merchant' => 'ATAMBUTU',
                        'reference' => $reference_code,
                        'amount' => $inputs['amount'],
                        'description' => __('miscellaneous.bank_transaction_description'),
                        'currency' => $request->currency,
                        'callbackUrl' => getApiURL() . '/payment/store',
                        'approve_url' => $request->app_url . '/donated/' . $inputs['amount'] . '/' . $request->currency . '/0/' . $current_user->id,
                        'cancel_url' => $request->app_url . '/donated/' . $inputs['amount'] . '/' . $request->currency . '/1/' . $current_user->id,
                        'decline_url' => $request->app_url . '/donated/' . $inputs['amount'] . '/' . $request->currency . '/2/' . $current_user->id,
                        'language' => app()->getLocale(),
                    ]);

                    if ($jsonRes->code != '0') {
                        return $this->handleError(__('notifications.error_while_processing'));

                    } else {
                        $object = new stdClass();

                        $object->result_response = [
                            'message' => $jsonRes->message,
                            'order_number' => $jsonRes->orderNumber,
                            'url' => $jsonRes->url
                        ];

                        // The donation is registered only if the processing succeed
                        $donation = Donation::create($inputs);

                        $object->donation = new ResourcesDonation($donation);

                        // Register payment, even if FlexPay will
                        $payment = Payment::where('order_number', $jsonRes->orderNumber)->first();

                        if (is_null($payment)) {
                            Payment::create([
                                'reference' => $reference,
                                'order_number' => $jsonRes->orderNumber,
                                'amount' => $inputs['amount'],
                                'currency' => $request->currency,
                                'type_id' => $request->transaction_type_id,
                                'donation_id' => $donation->id,
                                'user_id' => $inputs['user_id']
                            ]);
                        }

                        return $this->handleResponse($object, __('notifications.create_donation_success'));
                    }

                } else {
                    return $this->handleError(__('notifications.find_user_404'));
                }

            } else {
                $reference_code = 'REF-' . ((string) random_int(10000000, 99999999)) . '-ANONYMOUS';

                // Create response by sending request to FlexPay
                $jsonRes = $api_manager::call('POST', $gateway_card, config('services.flexpay.api_token'), [
                    'merchant' => 'ATAMBUTU',
                    'reference' => $reference_code,
                    'amount' => $inputs['amount'],
                    'description' => __('miscellaneous.bank_transaction_description'),
                    'currency' => $request->currency,
                    'callbackUrl' => getApiURL() . '/payment/store',
                    'approve_url' => $request->app_url . '/donated/' . $inputs['amount'] . '/' . $request->currency . '/0/anonymous',
                    'cancel_url' => $request->app_url . '/donated/' . $inputs['amount'] . '/' . $request->currency . '/1/anonymous',
                    'decline_url' => $request->app_url . '/donated/' . $inputs['amount'] . '/' . $request->currency . '/2/anonymous',
                    'language' => app()->getLocale(),
                ]);

                if ($jsonRes->code != '0') {
                    return $this->handleError(__('notifications.error_while_processing'));

                } else {
                    $object = new stdClass();

                    $object->result_response = [
                        'message' => $jsonRes->message,
                        'order_number' => $jsonRes->orderNumber,
                        'url' => $jsonRes->url
                    ];

                    // The donation is registered only if the processing succeed
                    $donation = Donation::create($inputs);

                    $object->donation = new ResourcesDonation($donation);

                    // Register payment, even if FlexPay will
                    $payment = Payment::where('order_number', $jsonRes->orderNumber)->first();

                    if (is_null($payment)) {
                        Payment::create([
                            'reference' => $reference,
                            'order_number' => $jsonRes->orderNumber,
                            'amount' => $inputs['amount'],
                            'currency' => $request->currency,
                            'type_id' => $request->transaction_type_id,
                            'donation_id' => $donation->id,
                        ]);
                    }

                    return $this->handleResponse($object, __('notifications.create_donation_success'));
                }
            }
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
            'pricing_id' => $request->pricing_id,
            'user_id' => $request->user_id
        ];

        if ($inputs['amount'] != null) {
            $donation->update([
                'amount' => $request->amount,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['pricing_id'] != null) {
            $donation->update([
                'pricing_id' => $request->pricing_id,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['user_id'] != null) {
            $donation->update([
                'user_id' => $request->user_id,
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
