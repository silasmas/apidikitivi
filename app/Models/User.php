<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
// use Laravel\Scout\Searchable;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable/*, Searchable*/;

    // const SEARCHABLE_FIELDS = ['firstname', 'national_number'];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    // public function toSearchableArray()
    // {
    //     return $this->only(self::SEARCHABLE_FIELDS);
    // }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['national_number', 'firstname', 'lastname', 'surname', 'gender', 'birth_city', 'birth_date', 'nationality', 'p_o_box', 'email', 'phone', 'email_verified_at', 'password', 'remember_token', 'api_token', 'avatar_url', 'updated_at', 'status_id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_connection' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];

    /**
     * MANY-TO-MANY
     * One status for several users
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_users');
    }

    /**
     * ONE-TO-MANY
     * One status for several users
     */
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * ONE-TO-ONE
     * One image for a user
     */
    public function image()
    {
        return $this->hasOne(Image::class);
    }

    /**
     * MANY-TO-ONE
     * Several role_users for a user
     */
    public function role_users()
    {
        return $this->hasMany(RoleUser::class);
    }

    /**
     * MANY-TO-ONE
     * Several addresses for a user
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * MANY-TO-ONE
     * Several offers for a user
     */
    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * MANY-TO-ONE
     * Several payments for a user
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * MANY-TO-ONE
     * Several messages for a user
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * MANY-TO-ONE
     * Several notifications for a user
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
