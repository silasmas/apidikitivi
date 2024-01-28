<?php

namespace App\Http\Controllers\API;

use App\Models\Donation;
use Illuminate\Http\Request;
use App\Http\Resources\Donation as ResourcesDonation;

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
        $donations = Donation::orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesDonation::collection($donations), __('notifications.find_all_donations_success'));
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
            'amount' => $request->amount,
            'pricing_id' => $request->pricing_id,
            'user_id' => $request->user_id
        ];

        // Validate required fields
        if (trim($inputs['amount']) == null) {
            return $this->handleError($inputs['amount'], __('validation.required'), 400);
        }

        $donation = Donation::create($inputs);

        return $this->handleResponse(new ResourcesDonation($donation), __('notifications.create_donation_success'));
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
