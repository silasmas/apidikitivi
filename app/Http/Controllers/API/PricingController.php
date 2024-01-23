<?php

namespace App\Http\Controllers\API;

use App\Models\Pricing;
use Illuminate\Http\Request;
use App\Http\Resources\Pricing as ResourcesPricing;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class PricingController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pricings = Pricing::all();

        return $this->handleResponse(ResourcesPricing::collection($pricings), __('notifications.find_all_pricings_success'));
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
            'deadline' => $request->deadline,
            'price' => $request->price
        ];
        // Select all pricings to check unique constraint
        $pricings = Pricing::all();

        // Validate required fields
        if (trim($inputs['deadline']) == null) {
            return $this->handleError($inputs['deadline'], __('validation.required'), 400);
        }

        // Check if deadline already exists
        foreach ($pricings as $another_pricing):
            if ($another_pricing->deadline == $inputs['deadline']) {
                return $this->handleError($inputs['deadline'], __('validation.custom.deadline.exists'), 400);
            }
        endforeach;

        $pricing = Pricing::create($inputs);

        return $this->handleResponse(new ResourcesPricing($pricing), __('notifications.create_pricing_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pricing = Pricing::find($id);

        if (is_null($pricing)) {
            return $this->handleError(__('notifications.find_pricing_404'));
        }

        return $this->handleResponse(new ResourcesPricing($pricing), __('notifications.find_pricing_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Pricing  $pricing
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Pricing $pricing)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'deadline' => $request->deadline,
            'price' => $request->price
        ];
        // Select all pricings to check unique constraint
        $pricings = Pricing::all();
        $current_pricing = Pricing::find($inputs['id']);

        if ($inputs['deadline'] != null) {
            foreach ($pricings as $another_pricing):
                if ($current_pricing->deadline != $inputs['deadline']) {
                    if ($another_pricing->deadline == $inputs['deadline']) {
                        return $this->handleError($inputs['deadline'], __('validation.custom.deadline.exists'), 400);
                    }
                }
            endforeach;

            $pricing->update([
                'deadline' => $request->deadline,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['price'] != null) {
            $pricing->update([
                'price' => $request->price,
                'updated_at' => now(),
            ]);
        }

        return $this->handleResponse(new ResourcesPricing($pricing), __('notifications.update_pricing_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Pricing  $pricing
     * @return \Illuminate\Http\Response
     */
    public function destroy(Pricing $pricing)
    {
        $pricing->delete();

        $pricings = Pricing::all();

        return $this->handleResponse(ResourcesPricing::collection($pricings), __('notifications.delete_pricing_success'));
    }
}
