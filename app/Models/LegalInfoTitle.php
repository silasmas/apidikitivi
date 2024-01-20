<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class LegalInfoTitle extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

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
