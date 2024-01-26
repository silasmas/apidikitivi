<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @author Xanders
 * @see https://www.linkedin.com/in/xanders-samoth-b2770737/
 */
class LegalInfoSubject extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'subject_name_en' => $this->subject_name->en,
            'subject_name_fr' => $this->subject_name->fr,
            'subject_name_ln' => $this->subject_name->ln,
            'subject_description_en' => $this->subject_description->en,
            'subject_description_fr' => $this->subject_description->fr,
            'subject_description_ln' => $this->subject_description->ln,
            'legal_info_titles' => LegalInfoTitle::collection($this->legal_info_titles),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s')
        ];
    }
}
