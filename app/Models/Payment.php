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

    protected $fillable = ['reference', 'provider_reference', 'order_number', 'amount', 'amount_customer', 'phone', 'currency', 'channel', 'updated_at', 'type_id', 'status_id', 'user_id'];

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
     * One user for several payments
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
