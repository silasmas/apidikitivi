<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Laravel\Scout\Searchable;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class Type extends Model
{
    use HasFactory/*, Searchable*/;

    // const SEARCHABLE_FIELDS = ['type_name'];

    protected $fillable = ['type_name', 'type_description', 'updated_at', 'group_id'];

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
     * One group for several types
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * MANY-TO-ONE
     * Several addresses for a type
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * MANY-TO-ONE
     * Several images for a type
     */
    public function images()
    {
        return $this->hasMany(Image::class);
    }

    /**
     * MANY-TO-ONE
     * Several payments for a type
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
