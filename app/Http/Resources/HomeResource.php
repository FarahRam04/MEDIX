<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'name'=>$this->employee->first_name.' '.$this->employee->last_name,
            'image'=>$this->image_url,
            'specialty'=>$this->specialist,
            'rate'=>$this->final_rating,
            'treatments'=>$this->number_of_treatments
        ];
    }
}
