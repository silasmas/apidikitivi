<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class Order extends Model
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
     * One book for several orders
     */
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * ONE-TO-MANY
     * One media for several orders
     */
    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * ONE-TO-MANY
     * One pricing for several orders
     */
    public function pricing()
    {
        return $this->belongsTo(Pricing::class);
    }

    /**
     * ONE-TO-MANY
     * One cart for several orders
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
}
