<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class Payment extends Model
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
     * One type for several payments
     */
    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * ONE-TO-MANY
     * One status for several payments
     */
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * ONE-TO-MANY
     * One cart for several payments
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * ONE-TO-MANY
     * One donation for several payments
     */
    public function donation()
    {
        return $this->belongsTo(Donation::class);
    }

    /**
     * ONE-TO-MANY
     * One user for several payments
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
