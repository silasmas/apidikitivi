<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Laravel\Scout\Searchable;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class Group extends Model
{
    use HasFactory/*, Searchable*/;

    // const SEARCHABLE_FIELDS = ['group_name'];

    protected $fillable = ['group_name', 'group_description', 'updated_at'];

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
     * MANY-TO-ONE
     * Several types for a group
     */
    public function types()
    {
        return $this->hasMany(Type::class);
    }

    /**
     * MANY-TO-ONE
     * Several statuses for a group
     */
    public function statuses()
    {
        return $this->hasMany(Status::class);
    }
}
