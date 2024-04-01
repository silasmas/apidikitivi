<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class Session extends Model
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
     * Several medias for several sessions
     */
    public function medias()
    {
        return $this->belongsToMany(Media::class)->withPivot(['is_viewed', 'created_at']);
    }

    /**
     * ONE-TO-MANY
     * One user for several sessions
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
