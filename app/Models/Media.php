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

    protected $table = 'medias';

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
        return $this->belongsToMany(Session::class)->orderByPivot('created_at', 'desc')->withTimestamps()->withPivot('is_viewed');
    }

    /**
     * MANY-TO-MANY
     * Several users for several medias
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->orderByPivot('created_at', 'desc')->withTimestamps()->withPivot(['is_liked', 'status_id']);
    }

    /**
     * MANY-TO-MANY
     * Several categories for several medias
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class);
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
     * ONE-TO-MANY
     * One user for several medias
     */
    public function user_owner()
    {
        return $this->belongsTo(User::class);
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
