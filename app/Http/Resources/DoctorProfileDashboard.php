<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorProfileDashboard extends JsonResource
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
            'department_id'=>$this->department->id,
            'image'=>$this->image_url,
            'name'=>$this->employee->first_name.' '.$this->employee->last_name,
            'speciality'=>$this->getTranslation('specialist','en'),
            'rate'=>$this->final_rating,
            'experience'=>$this->years_of_experience,
            'treatments'=>$this->number_of_treatments,
            'bio'=>$this->bio,
            'qualifications'=>$this->qualifications->pluck('name'),
            'medical_license_number'=>$this->medical_license_number
        ];
    }
}
