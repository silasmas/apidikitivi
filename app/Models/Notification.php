<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['notification_url', 'notification_content', 'notif_name', 'status_id', 'user_id'];

    /**
     * ONE-TO-MANY
     * One status for several notifications
     */
    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * ONE-TO-MANY
     * One user for several notifications
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
