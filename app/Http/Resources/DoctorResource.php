<?php

namespace App\Http\Resources;

use App\HelperFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @method getTranslation(string $string, string $locale)
 */
class DoctorResource extends JsonResource
{
    use HelperFunctions;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale=app()->getLocale();

        return [
            'id'=>$this->id,
            'department_id'=>$this->department->id,
            'shift'=>$this->employee->time->start_time === '09:00:00' ?'morning':'afternoon',
            'image'=>$this->image_url,
            'name'=>$this->employee->first_name.' '.$this->employee->last_name,
            'speciality'=>$this->getTranslation('specialist',$locale),
            'start_time'=>$this->employee->time->start_time,
            'end_time'=>$this->employee->time->end_time,
            'rate'=>$this->final_rating,
            'experience'=>$this->years_of_experience,
            'treatments'=>$this->number_of_treatments,
            'bio'=>$this->bio,
            'qualifications'=>$this->qualifications->pluck('name'),
        ];
    }
}
