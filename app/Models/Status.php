<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Laravel\Scout\Searchable;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class Status extends Model
{
    use HasFactory/*, Searchable*/;

    // const SEARCHABLE_FIELDS = ['status_name'];

    protected $fillable = ['status_name', 'status_description', 'color', 'updated_at', 'group_id'];

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    // public function toSearchableArray()
    // {
    //     return $this->only(self::SEARCHABLE_FIELDS);
    // }

    /**
     * ONE-TO-MANY
     * One group for several statuses
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * MANY-TO-ONE
     * Several users for a status
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * MANY-TO-ONE
     * Several payments for a status
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * MANY-TO-ONE
     * Several notifications for a status
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
