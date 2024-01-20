<?php

namespace App\Http\Resources;

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
        $roles_user = RoleUser::collection($this->role_users);

        return [
            'id' => $this->id,
            'serial_number' => $this->national_number,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'surname' => $this->surname,
            'gender' => $this->gender,
            'birth_city' => $this->birth_city,
            'birth_date' => $this->birth_date,
            'nationality' => $this->nationality,
            'p_o_box' => $this->p_o_box,
            'email' => $this->email,
            'phone' => $this->phone,
            'email_verified_at' => $this->email_verified_at,
            'password' => $this->password,
            'remember_token' => $this->remember_token,
            'api_token' => $this->api_token,
            'avatar_url' => $this->avatar_url != null ? (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/public/storage/' . $this->avatar_url : null,
            // 'avatar_url' => $this->avatar_url != null ? '/storage/' . $this->avatar_url : null,
            'identity_data' => Image::make($this->image),
            'status' => Status::make($this->status),
            'addresses' => Address::collection($this->addresses),
            'role_user' => $roles_user[0],
            'offers' => Offer::collection($this->offers)->sortByDesc('created_at')->toArray(),
            'payments' => Payment::collection($this->payments)->sortByDesc('created_at')->toArray(),
            'notifications' => Notification::collection($this->notifications)->sortByDesc('created_at')->toArray(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
}
