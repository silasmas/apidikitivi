<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Playback / performance log sent by the apps.
 */
class PlaybackEvent extends Model
{
    use HasFactory;

    protected $table = 'playback_events';

    public $timestamps = false;

    /**
     * Events accepted from the apps.
     */
    public const EVENTS = [
        'video_play', 'video_complete', 'video_abandon', 'playback_error',
        'buffer_start', 'buffer_end', 'video_startup_ms', 'thumbnail_load_ms',
        'api_error', 'recommendation_click', 'autoplay_next', 'autoplay_cancel',
    ];

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
        'props' => 'array',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * ONE-TO-MANY
     * One media for several events
     */
    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * ONE-TO-MANY
     * One user for several events
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
