<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class Cart extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * ONE-TO-MANY
     * One type for several carts
     */
    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * ONE-TO-MANY
     * One status for several carts
     */
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * ONE-TO-MANY
     * One user for several carts
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * MANY-TO-ONE
     * Several orders for a cart
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * MANY-TO-ONE
     * Several payments for a cart
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
