<?php

namespace App\Http\Resources;

use App\Models\Notification as NotificationModel;
use App\Models\Cart as CartModel;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class User extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $unread_notifications = NotificationModel::where([['status_id', 11], ['user_id', $this->id]])->orderByDesc('created_at')->get();
        $user_watchlist = CartModel::where([['user_id', $this->id], ['type_id', 14]])->first();

        if (is_null($user_watchlist)) {
            $user_watchlist = CartModel::create([
                'type_id' => 14,
                'user_id' => $this->id
            ]);
        }

        return [
            'id' => $this->id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'surname' => $this->surname,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date,
            'age' => !empty($this->birth_date) ? $this->age() : null,
            'city' => $this->city,
            'address_1' => $this->address_1,
            'address_2' => $this->address_2,
            'p_o_box' => $this->p_o_box,
            'email' => $this->email,
            'phone' => $this->phone,
            'username' => $this->username,
            'password' => $this->password,
            'belongs_to' => $this->belongs_to,
            'parental_code' => $this->parental_code,
            'email_verified_at' => $this->email_verified_at,
            'phone_verified_at' => $this->phone_verified_at,
            'remember_token' => $this->remember_token,
            'api_token' => $this->api_token,
            'prefered_theme' => $this->prefered_theme,
            'avatar_url' => $this->avatar_url != null ? (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/public/storage/' . $this->avatar_url : (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/public/assets/img/user.png',
            'id_card_type' => $this->id_card_type,
            'id_card_recto' => $this->id_card_recto != null ? (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/public/storage/' . $this->id_card_recto : null,
            'id_card_verso' => $this->id_card_verso != null ? (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/public/storage/' . $this->id_card_verso : null,
            'status' => Status::make($this->status),
            'country' => Country::make($this->country),
            'roles' => Role::collection($this->roles),
            'owned_medias' => Media::collection($this->owned_medias)->sortByDesc('created_at')->toArray(),
            'payments' => Payment::collection($this->payments)->sortByDesc('created_at')->toArray(),
            'notifications' => Notification::collection($this->notifications)->sortByDesc('created_at')->toArray(),
            'unread_notifications' => Notification::collection($unread_notifications)->toArray($request),
            'watchlist' => Cart::make($user_watchlist)->toArray($request),
            'watchlist_id' => $user_watchlist->id,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
}
