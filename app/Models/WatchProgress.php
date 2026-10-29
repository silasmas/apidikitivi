<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Playback history of a profile (resume position, completed, play count).
 */
class WatchProgress extends Model
{
    use HasFactory;

    protected $table = 'watch_progress';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'completed' => 'boolean',
        'last_watched_at' => 'datetime',
    ];

    /**
     * ONE-TO-MANY
     * One media for several watch progress entries
     */
    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * ONE-TO-MANY
     * One user for several watch progress entries
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
