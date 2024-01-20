<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Laravel\Scout\Searchable;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class LegalInfoTitle extends Model
{
    use HasFactory/*, Searchable*/;

    // const SEARCHABLE_FIELDS = ['title'];

    protected $fillable = ['title', 'updated_at', 'legal_info_subject_id'];

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
     * One legal info subject for several legal infos titles
     */
    public function legal_info_subject()
    {
        return $this->belongsTo(LegalInfoSubject::class);
    }

    /**
     * MANY-TO-ONE
     * Several legal infos contents for a legal infos titles
     */
    public function legal_info_contents()
    {
        return $this->hasMany(LegalInfoContent::class);
    }
}
