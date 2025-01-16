<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

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

    /**
     * Current trends
     */
    public static function getMediaSessions($year, $for_youth)
    {
        // Start the query with the association table
        $query = self::join('media_session', 'media_session.media_id', '=', 'medias.id')
                        ->leftJoin('sessions', 'media_session.session_id', '=', 'sessions.id')
                        ->whereYear('media_session.created_at', $year)
                        ->orWhereNull('media_session.session_id');

        // Add condition for "for_youth" and "is_public" dynamically
        if ($for_youth == 0) {
            $query->where('medias.is_public', 1);

        } else {
            $query->where([['medias.for_youth', $for_youth], ['medias.is_public', 1]]);
        }

        // Select the necessary columns and limit the results
        return $query->select('medias.*')->limit(5)->get();
    }
}
