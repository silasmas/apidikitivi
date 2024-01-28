<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class Donation extends Model
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
     * One pricing for several donations
     */
    public function pricing()
    {
        return $this->belongsTo(Pricing::class);
    }

    /**
     * ONE-TO-MANY
     * One user for several donations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * MANY-TO-ONE
     * Several payments for a donation
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
