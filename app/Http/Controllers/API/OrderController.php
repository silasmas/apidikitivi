<?php

namespace App\Http\Controllers\API;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Resources\Order as ResourcesOrder;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class OrderController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $orders = Order::orderByDesc('created_at')->get();

        return $this->handleResponse(ResourcesOrder::collection($orders), __('notifications.find_all_orders_success'));
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
            'book_id' => $request->book_id,
            'media_id' => $request->media_id,
            'pricing_id' => $request->pricing_id,
            'cart_id' => $request->cart_id
        ];

        // Validate required fields
        if (trim($inputs['cart_id']) == null) {
            return $this->handleError($inputs['cart_id'], __('validation.required'), 400);
        }

        $order = Order::create($inputs);

        return $this->handleResponse(new ResourcesOrder($order), __('notifications.create_order_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = Order::find($id);

        if (is_null($order)) {
            return $this->handleError(__('notifications.find_order_404'));
        }

        return $this->handleResponse(new ResourcesOrder($order), __('notifications.find_order_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        // Get inputs
        $inputs = [
            'book_id' => $request->book_id,
            'media_id' => $request->media_id,
            'pricing_id' => $request->pricing_id,
            'cart_id' => $request->cart_id
        ];

        if ($inputs['book_id'] != null) {
            $order->update([
                'book_id' => $request->book_id,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['media_id'] != null) {
            $order->update([
                'media_id' => $request->media_id,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['pricing_id'] != null) {
            $order->update([
                'pricing_id' => $request->pricing_id,
                'updated_at' => now(),
            ]);
        }

        if ($inputs['cart_id'] != null) {
            $order->update([
                'cart_id' => $request->cart_id,
                'updated_at' => now(),
            ]);
        }

        return $this->handleResponse(new ResourcesOrder($order), __('notifications.update_order_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        $order->delete();

        $orders = Order::all();

        return $this->handleResponse(ResourcesOrder::collection($orders), __('notifications.delete_order_success'));
    }
}
