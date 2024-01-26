<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class Media extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * MANY-TO-MANY
     * Several sessions for several medias
     */
    public function sessions()
    {
        return $this->belongsToMany(Session::class);
    }

    /**
     * MANY-TO-MANY
     * Several user_approbations for several medias
     */
    public function user_approbations()
    {
        return $this->belongsToMany(User::class)->withPivot('created_at', 'updated_at', 'status_id');
    }

    /**
     * ONE-TO-MANY
     * One type for several medias
     */
    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * MANY-TO-ONE
     * Several parts for a media
     */
    public function parts()
    {
        return $this->hasMany(Part::class);
    }

    /**
     * MANY-TO-ONE
     * Several orders for a media
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
