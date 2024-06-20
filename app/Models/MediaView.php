<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class MediaView extends Model
{
    use HasFactory;

    protected $table = 'media_views';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * ONE-TO-MANY
     * One media for several media_views
     */
    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * ONE-TO-MANY
     * One user for several media_views
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
