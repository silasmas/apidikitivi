<?php

namespace App\Http\Controllers\API;

use App\Models\Cart;
use App\Models\Media;
use App\Models\Order;
use App\Models\Type;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\Cart as ResourcesCart;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class CartController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $carts = Cart::all();

        return $this->handleResponse(ResourcesCart::collection($carts), __('notifications.find_all_carts_success'));
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
            'payment_code' => $request->payment_code,
            'type_id' => $request->type_id,
            'status_id' => $request->status_id,
            'user_id' => $request->user_id
        ];
        // Select all carts to check unique constraint
        $carts = Cart::all();

        // Validate required fields
        if (trim($inputs['user_id']) == null) {
            return $this->handleError($inputs['user_id'], __('validation.required'), 400);
        }

        // Check if cart payment code already exists
        foreach ($carts as $another_cart):
            if ($another_cart->payment_code == $inputs['payment_code']) {
                return $this->handleError($inputs['payment_code'], __('validation.custom.code.exists'), 400);
            }
        endforeach;

        $cart = Cart::create($inputs);

        return $this->handleResponse(new ResourcesCart($cart), __('notifications.create_cart_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $cart = Cart::find($id);

        if (is_null($cart)) {
            return $this->handleError(__('notifications.find_cart_404'));
        }

        return $this->handleResponse(new ResourcesCart($cart), __('notifications.find_cart_success'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Cart $cart)
    {
        // Get inputs
        $inputs = [
            'id' => $request->id,
            'payment_code' => $request->payment_code,
            'type_id' => $request->type_id,
            'status_id' => $request->status_id,
            'user_id' => $request->user_id
        ];
        // Select all carts to check unique constraint
        $carts = Cart::all();
        $current_cart = Cart::find($inputs['id']);

        if ($inputs['payment_code'] != null) {
            foreach ($carts as $another_cart):
                if ($current_cart->payment_code != $inputs['payment_code']) {
                    if ($another_cart->payment_code == $inputs['payment_code']) {
                        return $this->handleError($inputs['payment_code'], __('validation.custom.code.exists'), 400);
                    }
                }
            endforeach;

            $cart->update([
                'payment_code' => $inputs['payment_code'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['type_id'] != null) {
            $cart->update([
                'type_id' => $inputs['type_id'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['status_id'] != null) {
            $cart->update([
                'status_id' => $inputs['status_id'],
                'updated_at' => now(),
            ]);
        }

        if ($inputs['user_id'] != null) {
            $cart->update([
                'user_id' => $inputs['user_id'],
                'updated_at' => now(),
            ]);
        }

        return $this->handleResponse(new ResourcesCart($cart), __('notifications.update_cart_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cart $cart)
    {
        $cart->delete();

        $carts = Cart::all();

        return $this->handleResponse(ResourcesCart::collection($carts), __('notifications.delete_cart_success'));
    }

    // ==================================== CUSTOM METHODS ====================================
    /**
     * Get user cart by type.
     *
     * @param  int $user_id
     * @param  int $type_id
     * @return \Illuminate\Http\Response
     */
    public function findByType($user_id, $type_id)
    {
        $user = User::find($user_id);

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        $type = Type::find($type_id);

        if (is_null($type)) {
            return $this->handleError(__('notifications.find_type_404'));
        }

        if ($type->type_name == 'Watchlist') {
            $cart = Cart::where([['user_id', $user->id], ['type_id', $type->id]])->first();

            if (is_null($cart)) {
                $cart = Cart::create([
                    'type_id' => $type->id,
                    'user_id' => $user->id
                ]);
            }

            return $this->handleResponse(new ResourcesCart($cart), __('notifications.find_cart_success'));
            
        } else {
            $carts = Cart::where([['user_id', $user_id], ['type_id', $type->id]])->get();

            return $this->handleResponse(ResourcesCart::collection($carts), __('notifications.find_all_carts_success'));
        }
    }

    /**
     * Check if media is in cart or watchlist.
     *
     * @param  int $media_id
     * @param  int $user_id
     * @param  int $type_id
     * @return \Illuminate\Http\Response
     */
    public function isInside($media_id, $user_id, $type_id)
    {
        $media = Media::find($media_id);

        if (is_null($media)) {
            return $this->handleError(__('notifications.find_media_404'));
        }

        $user = User::find($user_id);

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        $type = Type::find($type_id);

        if (is_null($type)) {
            return $this->handleError(__('notifications.find_type_404'));
        }

        $cart = Cart::where([['user_id', $user->id], ['type_id', $type->id]])->first();

        if (inArrayR($media->id, $cart->orders, 'media_id')) {
            return $this->handleResponse(1, __('notifications.find_media_success'), null);

        } else {
            return $this->handleResponse(0, __('notifications.find_media_404'), null);
        }
    }

    /**
     * Add media to cart or watchlist.
     *
     * @param  string $locale
     * @param  string $type_name
     * @param  int $media_id
     * @param  int $user_id
     * @return \Illuminate\Http\Response
     */
    public function addToCart($locale, $type_name, $media_id, $user_id)
    {
        $media = Media::find($media_id);
        $user = User::find($user_id);
        $type = Type::where('type_name->' . $locale, $type_name)->first();

        if (is_null($media)) {
            return $this->handleError(__('notifications.find_media_404'));
        }

        if (is_null($user)) {
            return $this->handleError(__('notifications.find_user_404'));
        }

        if (is_null($type)) {
            return $this->handleError(__('notifications.find_type_404'));
        }

        $cart = Cart::where([['user_id', $user->id], ['type_id', $type->id]])->first();

        if ($cart != null) {
            Order::create([
                'media_id' => $media->id,
                'cart_id' => $cart->id
            ]);

            return $this->handleResponse(new ResourcesCart($cart), __('notifications.find_cart_success'));

        } else {
            $cart = Cart::create([
                'type_id' => $type->id,
                'user_id' => $user->id
            ]);

            Order::create([
                'media_id' => $media->id,
                'cart_id' => $cart->id
            ]);

            return $this->handleResponse(new ResourcesCart($cart), __('notifications.find_cart_success'));
        }
    }

    /**
     * Remove media from cart or watchlist.
     *
     * @param  int $cart_id
     * @param  int $media_id
     * @return \Illuminate\Http\Response
     */
    public function removeFromCart($cart_id, $media_id)
    {
        $cart = Cart::find($cart_id);
        $media = Media::find($media_id);

        if (is_null($cart)) {
            return $this->handleError(__('notifications.find_cart_404'));
        }

        if (is_null($media)) {
            return $this->handleError(__('notifications.find_media_404'));
        }

        $order = Order::where([['media_id', $media->id], ['cart_id', $cart->id]])->first();

        $order->delete();

        return $this->handleResponse(new ResourcesCart($cart), __('notifications.delete_media_success'));
    }
}
